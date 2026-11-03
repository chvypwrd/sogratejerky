<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderEvent;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\DeletedTestOrder;
use App\Models\DeletedTestOrderPaymentIntent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\Product;
use App\Models\StripeEvent;
use App\Models\User;
use App\Services\PaymentReconciler;
use App\Services\StripeGateway;
use App\Services\TestOrderCleanupService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\FakeStripeGateway;
use Tests\TestCase;

class TestOrderCleanupTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeGateway $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->stripe = new FakeStripeGateway;
        $this->app->instance(StripeGateway::class, $this->stripe);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    private function eligibleOrder(?bool $livemode = false): array
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $order = Order::factory()->paid()->shipping()->create([
            'user_id' => $user->id,
            'is_test_order' => true,
            'order_number' => 'SGR-CLEANUP-'.str()->upper(str()->random(6)),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'variant_name' => '8 oz',
            'sku' => 'SGR-CLEANUP-ITEM',
            'quantity' => 2,
            'unit_price_cents' => 1000,
            'line_total_cents' => 2000,
            'weight_grams' => 227,
        ]);

        $order->activities()->create([
            'event' => OrderEvent::OrderCreated->value,
            'previous_status' => null,
            'new_status' => 'placed',
            'actor_id' => null,
            'actor_name' => 'Customer',
            'note' => 'Order created at checkout.',
            'occurred_at' => now(),
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_cleanup_'.$order->id,
            'amount_cents' => $order->total_cents,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'stripe_livemode' => $livemode,
        ]);

        PaymentRefund::create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'stripe_refund_id' => 're_cleanup_'.$order->id,
            'request_key' => 'cleanup-refund-'.$order->id,
            'amount_cents' => 500,
            'currency' => 'USD',
            'status' => RefundStatus::Succeeded,
            'refunded_at' => now(),
        ]);

        return [$order, $user, $product, $payment];
    }

    public function test_verified_historical_test_order_previews_and_deletes_all_dependent_records(): void
    {
        [$order, $owner, $product, $payment] = $this->eligibleOrder(false);

        StripeEvent::create([
            'stripe_event_id' => 'evt_cleanup_preserved',
            'type' => 'payment_intent.succeeded',
            'status' => 'processed',
            'processed_at' => now(),
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.test-cleanup'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Eligible')
            ->assertSee('local_stripe_livemode');

        $this->actingAs($admin)
            ->get(route('admin.orders.test-cleanup.preview', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('irreversible')
            ->assertSee('Verified test-mode order');

        $this->actingAs($admin)
            ->delete(route('admin.orders.test-cleanup.destroy', $order), [
                'confirm_order_number' => $order->order_number,
            ])
            ->assertRedirect(route('admin.orders.test-cleanup'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('payment_refunds', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('order_activities', ['order_id' => $order->id]);
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('stripe_events', ['stripe_event_id' => 'evt_cleanup_preserved']);
        $this->assertCount(0, $this->stripe->refunds);

        $audit = DeletedTestOrder::firstOrFail();

        $this->assertSame($order->order_number, $audit->order_number);
        $this->assertSame($admin->id, $audit->deleted_by);
        $this->assertSame($admin->name, $audit->deleted_by_name);
        $this->assertSame('local_stripe_livemode', $audit->verification_basis);
        $this->assertSame(1, $audit->record_counts['order_items']);
        $this->assertSame(1, $audit->record_counts['payments']);
        $this->assertSame(1, $audit->record_counts['refunds']);
        $this->assertSame(1, $audit->record_counts['activities']);
        $this->assertSame($payment->payment_intent_id, DeletedTestOrderPaymentIntent::firstOrFail()->payment_intent_id);

        $this->actingAs($owner)
            ->get(route('account.orders.index'))
            ->assertOk()
            ->assertSee('You have not placed any orders yet.');
    }

    public function test_live_payment_order_cannot_be_deleted(): void
    {
        [$order] = $this->eligibleOrder(true);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Delete Test Order')
            ->assertSee('Permanent deletion unavailable');

        $this->actingAs($admin)
            ->get(route('admin.orders.test-cleanup.preview', $order))
            ->assertOk()
            ->assertSee('contains a live Stripe payment')
            ->assertDontSee('Permanently Delete Test Order');

        $this->actingAs($admin)
            ->delete(route('admin.orders.test-cleanup.destroy', $order), [
                'confirm_order_number' => $order->order_number,
            ])
            ->assertSessionHasErrors('order');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseCount('deleted_test_orders', 0);
    }

    public function test_optional_test_mode_key_can_verify_historical_order_without_local_evidence(): void
    {
        [$order] = $this->eligibleOrder(null);
        $payment = $order->payments()->firstOrFail();

        $this->stripe->hasTestVerificationKey = true;
        $this->stripe->testModeIntents[$payment->payment_intent_id] = (object) [
            'id' => $payment->payment_intent_id,
            'livemode' => false,
            'amount' => $payment->amount_cents,
            'currency' => strtolower($payment->currency),
            'metadata' => (object) ['order_id' => (string) $order->id],
        ];

        $this->actingAs($this->admin())
            ->get(route('admin.orders.test-cleanup.preview', $order))
            ->assertOk()
            ->assertSee('read_only_stripe_test_key')
            ->assertSee('Permanently Delete Test Order');

        $this->assertFalse($payment->fresh()->stripe_livemode);
    }

    public function test_ambiguous_or_unavailable_payment_evidence_refuses_deletion(): void
    {
        [$order] = $this->eligibleOrder(null);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.test-cleanup.preview', $order))
            ->assertOk()
            ->assertSee('verification is unavailable or inconclusive')
            ->assertDontSee('Permanently Delete Test Order');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_mixed_test_and_live_payment_order_is_ineligible(): void
    {
        [$order] = $this->eligibleOrder(false);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'payment_intent_id' => 'pi_cleanup_live_'.$order->id,
            'amount_cents' => 100,
            'currency' => 'USD',
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'stripe_livemode' => true,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.test-cleanup.preview', $order))
            ->assertOk()
            ->assertSee('contains a live Stripe payment')
            ->assertDontSee('Permanently Delete Test Order');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_deletion_requires_exact_order_number_confirmation(): void
    {
        [$order] = $this->eligibleOrder(false);

        $this->actingAs($this->admin())
            ->delete(route('admin.orders.test-cleanup.destroy', $order), [
                'confirm_order_number' => 'WRONG-ORDER',
            ])
            ->assertSessionHasErrors('confirm_order_number');

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseCount('deleted_test_orders', 0);
    }

    public function test_cleanup_requires_refund_permission(): void
    {
        [$order] = $this->eligibleOrder(false);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.orders.test-cleanup'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.orders.test-cleanup.preview', $order))
            ->assertForbidden();
    }

    public function test_database_failure_rolls_back_dependents_and_audit_record(): void
    {
        [$order] = $this->eligibleOrder(false);
        $admin = $this->admin();

        Order::deleting(function () {
            throw new RuntimeException('Simulated deletion failure.');
        });

        try {
            app(TestOrderCleanupService::class)->delete($order, $admin);
            $this->fail('Expected simulated deletion failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated deletion failure.', $exception->getMessage());
        } finally {
            Order::flushEventListeners();
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id]);
        $this->assertDatabaseHas('payment_refunds', ['order_id' => $order->id]);
        $this->assertDatabaseHas('order_activities', ['order_id' => $order->id]);
        $this->assertDatabaseCount('deleted_test_orders', 0);
    }

    public function test_late_test_mode_webhook_for_deleted_order_is_ignored(): void
    {
        [$order, , , $payment] = $this->eligibleOrder(false);

        app(TestOrderCleanupService::class)->delete($order, $this->admin());

        $event = (object) [
            'id' => 'evt_late_cleanup_webhook',
            'type' => 'payment_intent.succeeded',
            'data' => (object) [
                'object' => (object) [
                    'id' => $payment->payment_intent_id,
                    'status' => 'succeeded',
                ],
            ],
        ];

        app(PaymentReconciler::class)->handleEvent($event);

        $this->assertDatabaseHas('stripe_events', [
            'stripe_event_id' => 'evt_late_cleanup_webhook',
            'status' => 'processed',
        ]);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }
}
