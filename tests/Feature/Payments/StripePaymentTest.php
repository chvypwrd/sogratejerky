<?php

namespace Tests\Feature\Payments;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderEvent;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Cart;
use App\Models\FulfillmentSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRateRule;
use App\Models\StripeEvent;
use App\Models\User;
use App\Notifications\AdminPaidOrderNotification;
use App\Notifications\OrderStatusNotification;
use App\Services\CheckoutAvailability;
use App\Services\PaymentReconciler;
use App\Services\SettingsService;
use App\Services\StripeGateway;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Mockery;
use Stripe\Exception\SignatureVerificationException;
use Tests\Support\FakeStripeGateway;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeGateway $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        FulfillmentSetting::current()->update([
            'shipping_enabled' => true,
            'local_delivery_enabled' => true,
            'pickup_enabled' => true,
            'shipping_basis' => 'subtotal',
        ]);

        ShippingRateRule::create([
            'min_value' => 0,
            'max_value' => 100000,
            'fee_cents' => 500,
            'is_active' => true,
        ]);

        $this->stripe = new FakeStripeGateway;
        $this->app->instance(StripeGateway::class, $this->stripe);
    }

    private function addCartItem(int $quantity = 2): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 1000,
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ])->assertRedirect();

        return [$product, $variant];
    }

    private function checkoutToken(): string
    {
        $token = str_repeat('b', 64);
        $this->withSession(['checkout_token' => $token]);

        return $token;
    }

    private function checkoutPayload(string $token, array $overrides = []): array
    {
        return array_merge([
            'checkout_token' => $token,
            'customer_name' => 'Test Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '555-0100',
            'fulfillment_method' => 'shipping',
            'billing_same_as_fulfillment' => '1',
            'fulfillment_recipient_name' => 'Test Buyer',
            'fulfillment_address_line_1' => '123 Main Street',
            'fulfillment_city' => 'Austin',
            'fulfillment_state' => 'TX',
            'fulfillment_postal_code' => '78701',
            'fulfillment_country' => 'United States',
        ], $overrides);
    }

    private function startCheckout(?User $user = null, array $overrides = []): array
    {
        if ($user) {
            $this->actingAs($user);
        }

        $token = $this->checkoutToken();
        $this->addCartItem();

        $response = $this->postJson(route('checkout.store'), $this->checkoutPayload($token, $overrides))
            ->assertOk();

        return [$response, Order::with('items')->firstOrFail()];
    }

    private function setNotificationEmail(string $email): void
    {
        app(SettingsService::class)->set('contact.notification_email', $email);
    }

    private function paymentForOrder(Order $order, string $status = PaymentStatus::Paid->value): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_test_'.$order->id,
            'stripe_customer_id' => null,
            'amount_cents' => $order->total_cents,
            'currency' => $order->currency,
            'status' => $status,
            'paid_at' => $status === PaymentStatus::Paid->value ? now() : null,
        ]);
    }

    private function paymentEvent(Payment $payment, string $type, array $overrides = []): object
    {
        return (object) [
            'id' => 'evt_'.Str::lower(Str::random(12)),
            'type' => $type,
            'data' => (object) [
                'object' => (object) array_merge([
                    'id' => $payment->payment_intent_id,
                    'status' => 'succeeded',
                    'amount' => $payment->amount_cents,
                    'currency' => strtolower($payment->currency),
                    'metadata' => (object) ['order_id' => (string) $payment->order_id],
                    'last_payment_error' => (object) [
                        'code' => 'card_declined',
                        'message' => 'Your card was declined.',
                    ],
                ], $overrides),
            ],
        ];
    }

    public function test_payment_intent_uses_server_total_and_customer_consent(): void
    {
        $user = User::factory()->create();

        [$response, $order] = $this->startCheckout($user, ['save_payment_method' => '1']);

        $response->assertOk()->assertJsonStructure([
            'client_secret',
            'customer_session_client_secret',
            'return_url',
            'publishable_key',
        ]);

        $payment = $order->payments()->firstOrFail();

        $this->assertSame(2500, $this->stripe->createdIntents[0]['amount']);
        $this->assertArrayNotHasKey('payment_method_types', $this->stripe->createdIntents[0]);
        $this->assertSame('cus_test_'.$user->id, $this->stripe->createdIntents[0]['customer']);
        $this->assertSame('off_session', $this->stripe->createdIntents[0]['setup_future_usage']);
        $this->assertSame(2500, $payment->amount_cents);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame('cus_test_'.$user->id, $user->fresh()->stripe_customer_id);
    }

    public function test_paid_order_queues_admin_notification_to_configured_recipient(): void
    {
        Notification::fake();
        $this->setNotificationEmail('orders@example.com');

        [, $order] = $this->startCheckout();
        $payment = $order->payments()->firstOrFail();

        app(PaymentReconciler::class)->handleEvent($this->paymentEvent($payment, 'payment_intent.succeeded'));

        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Placed, $order->order_status);
        Notification::assertSentOnDemand(
            AdminPaidOrderNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'orders@example.com'
                && $notification->order->is($order),
        );
        Notification::assertSentOnDemandTimes(AdminPaidOrderNotification::class, 1);
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);
    }

    public function test_admin_paid_order_notification_contains_required_details(): void
    {
        $order = Order::factory()->paid()->pickup()->create([
            'customer_name' => 'Taylor Buyer',
            'customer_email' => 'taylor@example.com',
            'total_cents' => 4210,
        ]);

        $mail = (new AdminPaidOrderNotification($order))->toMail(new AnonymousNotifiable);
        $lines = implode(' ', $mail->introLines);

        $this->assertSame('New paid SOGRATE order '.$order->order_number, $mail->subject);
        $this->assertStringContainsString($order->order_number, $lines);
        $this->assertStringContainsString('Taylor Buyer', $lines);
        $this->assertStringContainsString('taylor@example.com', $lines);
        $this->assertStringContainsString('$42.10', $lines);
        $this->assertStringContainsString('Pickup', $lines);
        $this->assertStringContainsString('Paid', $lines);
        $this->assertStringContainsString('Confirmed by Stripe', $lines);
        $this->assertSame(route('admin.orders.show', $order), $mail->actionUrl);
    }

    public function test_missing_notification_email_does_not_interrupt_payment_reconciliation(): void
    {
        Notification::fake();
        Log::spy();
        $this->setNotificationEmail('');

        [, $order] = $this->startCheckout();
        $payment = $order->payments()->firstOrFail();

        app(PaymentReconciler::class)->handleEvent($this->paymentEvent($payment, 'payment_intent.succeeded'));

        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Placed, $order->order_status);
        Notification::assertSentOnDemandTimes(AdminPaidOrderNotification::class, 0);
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'contact.notification_email')
                && ($context['order_id'] ?? null) === $order->id)
            ->once();
    }

    public function test_repeated_payment_success_events_queue_admin_notification_once(): void
    {
        Notification::fake();
        $this->setNotificationEmail('orders@example.com');

        [, $order] = $this->startCheckout();
        $payment = $order->payments()->firstOrFail();
        $reconciler = app(PaymentReconciler::class);

        $reconciler->handleEvent($this->paymentEvent($payment, 'payment_intent.succeeded'));
        $reconciler->handleEvent($this->paymentEvent($payment, 'payment_intent.succeeded'));

        $this->assertSame(2, StripeEvent::count());
        Notification::assertSentOnDemandTimes(AdminPaidOrderNotification::class, 1);
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);
    }

    public function test_successful_webhook_is_idempotent_and_consumes_only_ordered_cart_items(): void
    {
        Notification::fake();
        $this->setNotificationEmail('orders@example.com');

        [, $order] = $this->startCheckout();
        $payment = $order->payments()->firstOrFail();

        $unrelatedProduct = Product::factory()->create();
        $unrelatedVariant = ProductVariant::factory()->create([
            'product_id' => $unrelatedProduct->id,
            'price' => 500,
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $unrelatedProduct->id,
            'product_variant_id' => $unrelatedVariant->id,
            'quantity' => 1,
        ])->assertRedirect();

        $event = $this->paymentEvent($payment, 'payment_intent.succeeded');
        $reconciler = app(PaymentReconciler::class);

        $reconciler->handleEvent($event);
        $reconciler->handleEvent($event);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Placed, $order->order_status);
        $this->assertSame(FulfillmentStatus::Unfulfilled, $order->fulfillment_status);
        $this->assertSame(1, $order->activities()->where('event', OrderEvent::PaymentPaid->value)->count());
        $this->assertSame(1, StripeEvent::count());
        $this->assertSame(1, Cart::firstOrFail()->items()->count());
        $this->assertSame($unrelatedVariant->id, Cart::firstOrFail()->items()->first()->product_variant_id);
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);
        Notification::assertSentOnDemandTimes(AdminPaidOrderNotification::class, 1);
    }

    public function test_stale_failure_event_does_not_regress_paid_order(): void
    {
        Notification::fake();

        [, $order] = $this->startCheckout();
        $payment = $order->payments()->firstOrFail();
        $reconciler = app(PaymentReconciler::class);

        $reconciler->handleEvent($this->paymentEvent($payment, 'payment_intent.succeeded'));
        $reconciler->handleEvent($this->paymentEvent($payment, 'payment_intent.payment_failed'));

        $order->refresh();
        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(0, $order->activities()->where('event', OrderEvent::PaymentFailed->value)->count());
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);
    }

    public function test_failed_payment_remains_retryable_and_does_not_notify(): void
    {
        Notification::fake();
        $this->setNotificationEmail('orders@example.com');

        [, $order] = $this->startCheckout();
        $payment = $order->payments()->firstOrFail();

        $event = $this->paymentEvent($payment, 'payment_intent.payment_failed', [
            'status' => 'requires_payment_method',
        ]);

        app(PaymentReconciler::class)->handleEvent($event);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame(PaymentStatus::Failed, $order->payment_status);
        $this->assertSame(OrderStatus::PendingPayment, $order->order_status);
        $this->assertSame('card_declined', $payment->error_code);
        Notification::assertNothingSent();
        Notification::assertSentOnDemandTimes(AdminPaidOrderNotification::class, 0);
    }

    public function test_canceled_payment_does_not_queue_paid_order_notification(): void
    {
        Notification::fake();
        $this->setNotificationEmail('orders@example.com');

        [, $order] = $this->startCheckout();
        $payment = $order->payments()->firstOrFail();

        app(PaymentReconciler::class)->handleEvent($this->paymentEvent($payment, 'payment_intent.canceled', [
            'status' => 'canceled',
        ]));

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        Notification::assertNothingSent();
        Notification::assertSentOnDemandTimes(AdminPaidOrderNotification::class, 0);
    }

    public function test_browser_return_reconciles_verified_payment_status(): void
    {
        Notification::fake();

        [, $order] = $this->startCheckout();
        $payment = $order->payments()->firstOrFail();
        $this->stripe->intents[$payment->payment_intent_id]->status = 'succeeded';

        $url = URL::temporarySignedRoute(
            'checkout.return',
            now()->addDay(),
            ['order' => $order->order_number],
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('Payment confirmed');

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);
    }

    public function test_signed_webhook_controller_processes_verified_event(): void
    {
        Notification::fake();

        [, $order] = $this->startCheckout();
        $payment = $order->payments()->firstOrFail();
        $this->stripe->webhookEvent = $this->paymentEvent($payment, 'payment_intent.succeeded');

        $this->post(route('stripe.webhook'), [], ['Stripe-Signature' => 'test'])
            ->assertOk();

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
        Notification::assertSentOnDemandTimes(OrderStatusNotification::class, 1);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $mock = Mockery::mock(StripeGateway::class);
        $mock->shouldReceive('constructWebhookEvent')
            ->once()
            ->andThrow(SignatureVerificationException::factory('Invalid signature'));
        $this->app->instance(StripeGateway::class, $mock);

        $this->post(route('stripe.webhook'), [], ['Stripe-Signature' => 'invalid'])
            ->assertStatus(400);
    }

    public function test_failed_payment_can_retry_without_creating_another_intent(): void
    {
        Notification::fake();

        $token = $this->checkoutToken();
        $this->addCartItem();

        $this->postJson(route('checkout.store'), $this->checkoutPayload($token))->assertOk();

        $order = Order::firstOrFail();
        $payment = $order->payments()->firstOrFail();

        app(PaymentReconciler::class)->handleEvent($this->paymentEvent($payment, 'payment_intent.payment_failed', [
            'status' => 'requires_payment_method',
        ]));

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);

        $this->postJson(route('checkout.store'), $this->checkoutPayload($token))
            ->assertOk()
            ->assertJsonStructure(['client_secret']);

        $this->assertSame(1, Payment::count());
        $this->assertCount(1, $this->stripe->createdIntents);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $order->fresh()->payment_status);
    }

    public function test_customer_can_view_and_remove_only_their_saved_cards(): void
    {
        $user = User::factory()->create(['stripe_customer_id' => 'cus_test_123']);
        $this->stripe->savedMethods = [
            ['id' => 'pm_123', 'brand' => 'Visa', 'last4' => '4242', 'exp_month' => '12', 'exp_year' => '2030'],
        ];

        $this->actingAs($user)
            ->get(route('account.payment-methods.index'))
            ->assertOk()
            ->assertSee('4242')
            ->assertSee('Visa');

        $this->actingAs($user)
            ->delete(route('account.payment-methods.destroy', 'pm_123'))
            ->assertRedirect();

        $this->assertSame(['pm_123'], $this->stripe->detachedMethods);

        $this->actingAs($user)
            ->delete(route('account.payment-methods.destroy', 'other_pm_123'))
            ->assertForbidden();
    }

    public function test_admin_can_issue_partial_and_full_refunds_without_over_refunding(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');
        $this->actingAs($admin);

        $order = Order::factory()->paid()->shipping()->create([
            'subtotal_cents' => 2500,
            'fulfillment_fee_cents' => 0,
            'total_cents' => 2500,
        ]);
        $payment = $this->paymentForOrder($order);

        $this->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Remaining refundable')
            ->assertSee('$25.00');

        $firstKey = (string) Str::uuid();

        $this->post(route('admin.orders.refund', $order), [
            'amount' => '10.00',
            'reason' => 'Customer request',
            'request_key' => $firstKey,
        ])->assertRedirect();

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->status);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $order->payment_status);
        $this->assertSame(1000, $payment->refunded_cents);
        $this->assertSame(1, PaymentRefund::count());
        $this->assertSame(1, count($this->stripe->refunds));

        $this->post(route('admin.orders.refund', $order), [
            'amount' => '10.00',
            'reason' => 'Duplicate submission',
            'request_key' => $firstKey,
        ])->assertRedirect();

        $this->assertSame(1, PaymentRefund::count());
        $this->assertSame(1, count($this->stripe->refunds));

        $this->post(route('admin.orders.refund', $order), [
            'amount' => '20.00',
            'request_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('amount');

        $this->assertSame(1, PaymentRefund::count());

        $this->post(route('admin.orders.refund', $order), [
            'amount' => '15.00',
            'request_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $payment->refresh();
        $order->refresh();

        $this->assertSame(PaymentStatus::Refunded, $payment->status);
        $this->assertSame(PaymentStatus::Refunded, $order->payment_status);
        $this->assertSame(2500, $payment->refunded_cents);
        $this->assertSame(2, PaymentRefund::count());
        $this->assertCount(2, $this->stripe->refunds);
        $this->assertSame(RefundStatus::Succeeded, PaymentRefund::latest('id')->first()->status);

        $this->post(route('admin.orders.cancel', $order), ['reason' => 'Refunded and cancelled.'])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->order_status);
    }

    public function test_refund_webhook_finalizes_pending_refund(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');
        $this->actingAs($admin);

        $order = Order::factory()->paid()->shipping()->create([
            'subtotal_cents' => 2500,
            'fulfillment_fee_cents' => 0,
            'total_cents' => 2500,
        ]);
        $payment = $this->paymentForOrder($order);
        $this->stripe->refundStatus = 'pending';

        $this->post(route('admin.orders.refund', $order), [
            'amount' => '10.00',
            'request_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $refund = PaymentRefund::firstOrFail();
        $payment->refresh();
        $order->refresh();

        $this->assertSame(RefundStatus::Pending, $refund->status);
        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);

        $event = (object) [
            'id' => 'evt_refund_'.Str::lower(Str::random(8)),
            'type' => 'refund.updated',
            'data' => (object) [
                'object' => (object) [
                    'id' => $refund->stripe_refund_id,
                    'payment_intent' => $payment->payment_intent_id,
                    'amount' => 1000,
                    'currency' => 'usd',
                    'status' => 'succeeded',
                ],
            ],
        ];

        app(PaymentReconciler::class)->handleEvent($event);

        $this->assertSame(RefundStatus::Succeeded, $refund->fresh()->status);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $order->fresh()->payment_status);
        $this->assertSame(1, $order->activities()->where('event', OrderEvent::RefundCompleted->value)->count());
    }

    public function test_checkout_can_be_disabled(): void
    {
        config(['commerce.checkout_enabled' => false]);

        $this->assertFalse(app(CheckoutAvailability::class)->enabled());
        $this->get(route('checkout.index'))->assertNotFound();
    }
}
