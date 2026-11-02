<?php

namespace Tests\Feature\Cart;

use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\FulfillmentSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRateRule;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutPaymentService;
use App\Services\StripeGateway;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Support\FakeStripeGateway;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private FakeStripeGateway $stripe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->stripe = new FakeStripeGateway;
        $this->app->instance(StripeGateway::class, $this->stripe);

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
    }

    private function addCartItem(int $quantity = 2): ProductVariant
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

        return $variant;
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

    private function checkoutToken(): string
    {
        $token = str_repeat('a', 64);
        $this->withSession(['checkout_token' => $token]);

        return $token;
    }

    public function test_checkout_requires_a_non_empty_cart(): void
    {
        $this->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'));
    }

    public function test_checkout_blocks_carts_with_unavailable_items(): void
    {
        $token = $this->checkoutToken();
        $variant = $this->addCartItem();

        $variant->product->update(['is_active' => false]);

        $this->from(route('cart.index'))
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->from(route('checkout.index'))
            ->post(route('checkout.store'), $this->checkoutPayload($token))
            ->assertSessionHasErrors('cart');

        $this->assertSame(0, Order::count());
    }

    public function test_authenticated_customer_can_place_a_pending_order_with_shipping(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $token = $this->checkoutToken();
        $variant = $this->addCartItem(2);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Complete Your Order');

        $response = $this->postJson(route('checkout.store'), $this->checkoutPayload($token));

        $response->assertOk()
            ->assertJsonStructure(['client_secret', 'order_number', 'return_url', 'total_formatted']);

        $order = Order::with('items')->firstOrFail();

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('pending_payment', $order->order_status->value);
        $this->assertSame('pending', $order->payment_status->value);
        $this->assertSame('unfulfilled', $order->fulfillment_status->value);
        $this->assertSame(2000, $order->subtotal_cents);
        $this->assertSame(500, $order->fulfillment_fee_cents);
        $this->assertSame(0, $order->tax_cents);
        $this->assertSame(2500, $order->total_cents);
        $this->assertCount(1, $order->items);
        $this->assertSame($variant->sku, $order->items->first()->sku);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame('Test Buyer', $order->fulfillment_address_snapshot['recipient_name']);
        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'event' => 'order_created',
        ]);

        $this->assertSame(1, Cart::firstOrFail()->items()->count());
        $this->assertSame(2500, $this->stripe->createdIntents[0]['amount']);
        $this->assertSame(1, $order->payments()->count());
        $this->assertSame(PaymentStatus::Pending, $order->payments()->first()->status);
        $this->assertSame($token, session('checkout_token'));
    }

    public function test_guest_receives_a_signed_order_confirmation(): void
    {
        $this->stripe->intentStatus = 'succeeded';

        $token = $this->checkoutToken();
        $this->addCartItem();

        $response = $this->postJson(route('checkout.store'), $this->checkoutPayload($token))
            ->assertOk()
            ->assertJson(['already_paid' => true]);

        $order = Order::firstOrFail();
        $this->assertNull($order->user_id);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(0, Cart::firstOrFail()->items()->count());

        $this->get($response->json('redirect_url'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Your payment is confirmed');
    }

    public function test_local_delivery_rejects_unsupported_zip_codes(): void
    {
        $token = $this->checkoutToken();
        $this->addCartItem();

        $this->from(route('checkout.index'))
            ->post(route('checkout.store'), $this->checkoutPayload($token, [
                'fulfillment_method' => 'local_delivery',
                'fulfillment_postal_code' => '99999',
            ]))
            ->assertSessionHasErrors('fulfillment_postal_code');

        $this->assertSame(0, Order::count());
        $this->assertSame(1, Cart::firstOrFail()->items()->count());
    }

    public function test_pickup_records_selected_location_and_billing_details(): void
    {
        $location = PickupLocation::create([
            'name' => 'North Market',
            'address' => '100 Main Street',
            'fee_cents' => 300,
            'is_active' => true,
        ]);

        $token = $this->checkoutToken();
        $this->addCartItem(1);

        $this->postJson(route('checkout.store'), $this->checkoutPayload($token, [
            'fulfillment_method' => 'pickup',
            'pickup_location_id' => $location->id,
            'billing_same_as_fulfillment' => null,
            'billing_recipient_name' => 'Test Buyer',
            'billing_address_line_1' => '456 Oak Avenue',
            'billing_city' => 'Austin',
            'billing_state' => 'TX',
            'billing_postal_code' => '78702',
            'billing_country' => 'United States',
        ]))->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('pickup', $order->fulfillment_method->value);
        $this->assertSame(300, $order->fulfillment_fee_cents);
        $this->assertSame('North Market', $order->pickup_location_snapshot['name']);
        $this->assertSame('456 Oak Avenue', $order->billing_address_snapshot['address_line_1']);
        $this->assertNull($order->fulfillment_address_snapshot);
    }

    public function test_account_created_during_checkout_owns_resulting_order(): void
    {
        Notification::fake();

        $token = $this->checkoutToken();
        $this->addCartItem();

        $this->post(route('customer.register.store'), [
            'name' => 'New Checkout Buyer',
            'email' => 'new-checkout@example.com',
            'password' => 'password123456',
            'password_confirmation' => 'password123456',
            'return_to' => 'checkout',
        ])->assertRedirect(route('checkout.index'));

        $this->assertAuthenticated();

        $this->postJson(route('checkout.store'), $this->checkoutPayload($token, [
            'customer_name' => 'New Checkout Buyer',
            'customer_email' => 'new-checkout@example.com',
        ]))->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame(auth()->id(), $order->user_id);
        $this->assertSame('new-checkout@example.com', $order->customer_email);
    }

    public function test_duplicate_checkout_submission_is_idempotent(): void
    {
        $token = $this->checkoutToken();
        $this->addCartItem();

        $payload = $this->checkoutPayload($token);

        $this->postJson(route('checkout.store'), $payload)
            ->assertOk()
            ->assertJsonPath('checkout_token', $token);
        $this->postJson(route('checkout.store'), $payload)
            ->assertOk()
            ->assertJsonPath('checkout_token', $token);

        $this->assertSame($token, session('checkout_token'));
        $this->assertSame(1, Order::count());
        $this->assertSame(1, Payment::count());
        $this->assertCount(1, $this->stripe->createdIntents);
    }

    public function test_completed_checkout_rotates_token_and_creates_a_new_order(): void
    {
        $this->stripe->intentStatus = 'succeeded';

        $oldToken = $this->checkoutToken();
        $this->addCartItem();
        $paidResponse = $this->postJson(route('checkout.store'), $this->checkoutPayload($oldToken))
            ->assertOk()
            ->assertJson(['already_paid' => true]);

        $oldOrder = Order::firstOrFail();
        $confirmationUrl = $paidResponse->json('redirect_url');

        $this->assertSame(PaymentStatus::Paid, $oldOrder->payment_status);
        $this->assertSame(0, Cart::firstOrFail()->items()->count());

        $this->stripe->intentStatus = 'requires_payment_method';
        $this->addCartItem();

        $this->get(route('checkout.index'))->assertOk();

        $newToken = session('checkout_token');

        $this->assertIsString($newToken);
        $this->assertNotSame($oldToken, $newToken);

        $newResponse = $this->postJson(route('checkout.store'), $this->checkoutPayload($newToken))
            ->assertOk();

        $this->assertArrayNotHasKey('already_paid', $newResponse->json());

        $newOrder = Order::latest('id')->firstOrFail();

        $this->assertNotSame($oldOrder->id, $newOrder->id);
        $this->assertSame(PaymentStatus::Pending, $newOrder->payment_status);
        $this->assertSame($newToken, session('checkout_token'));

        $this->get($confirmationUrl)
            ->assertOk()
            ->assertSee($oldOrder->order_number);
    }

    public function test_completed_checkout_token_cannot_reuse_paid_order_for_new_cart_contents(): void
    {
        $this->stripe->intentStatus = 'succeeded';

        $oldToken = $this->checkoutToken();
        $this->addCartItem();
        $this->postJson(route('checkout.store'), $this->checkoutPayload($oldToken))
            ->assertOk()
            ->assertJson(['already_paid' => true]);

        $oldOrder = Order::firstOrFail();

        $this->stripe->intentStatus = 'requires_payment_method';
        $this->addCartItem();
        $this->withSession(['checkout_token' => $oldToken]);

        $response = $this->postJson(route('checkout.store'), $this->checkoutPayload($oldToken))
            ->assertOk();

        $newOrder = Order::latest('id')->firstOrFail();

        $this->assertArrayNotHasKey('already_paid', $response->json());
        $this->assertNotSame($oldOrder->id, $newOrder->id);
        $this->assertSame(2, Order::count());
        $this->assertNotSame($oldToken, session('checkout_token'));
    }

    public function test_partially_refunded_checkout_does_not_block_a_new_purchase(): void
    {
        $this->stripe->intentStatus = 'succeeded';

        $oldToken = $this->checkoutToken();
        $this->addCartItem();
        $this->postJson(route('checkout.store'), $this->checkoutPayload($oldToken))
            ->assertOk()
            ->assertJson(['already_paid' => true]);

        $oldOrder = Order::firstOrFail();
        $oldOrder->update(['payment_status' => PaymentStatus::PartiallyRefunded]);
        $oldOrder->payments()->firstOrFail()->update(['status' => PaymentStatus::PartiallyRefunded]);

        $this->stripe->intentStatus = 'requires_payment_method';
        $this->addCartItem();

        $this->get(route('checkout.index'))->assertOk();

        $newToken = session('checkout_token');

        $this->assertNotSame($oldToken, $newToken);

        $response = $this->postJson(route('checkout.store'), $this->checkoutPayload($newToken))
            ->assertOk();

        $this->assertArrayNotHasKey('already_paid', $response->json());
        $this->assertSame(PaymentStatus::PartiallyRefunded, $oldOrder->fresh()->payment_status);
        $this->assertSame(2, Order::count());
        $this->assertSame(PaymentStatus::Pending, Order::latest('id')->firstOrFail()->payment_status);
    }

    public function test_new_guest_browser_session_gets_a_distinct_checkout_identity(): void
    {
        $this->stripe->intentStatus = 'succeeded';

        $firstToken = $this->checkoutToken();
        $this->addCartItem();
        $this->postJson(route('checkout.store'), $this->checkoutPayload($firstToken))
            ->assertOk()
            ->assertJson(['already_paid' => true]);

        $firstOrder = Order::firstOrFail();

        $this->assertNull($firstOrder->user_id);

        $this->flushSession();
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];

        $this->stripe->intentStatus = 'requires_payment_method';
        $this->addCartItem();

        $this->get(route('checkout.index'))->assertOk();

        $secondToken = session('checkout_token');

        $this->assertNotSame($firstToken, $secondToken);

        $response = $this->postJson(route('checkout.store'), $this->checkoutPayload($secondToken))
            ->assertOk();

        $this->assertArrayNotHasKey('already_paid', $response->json());

        $secondOrder = Order::latest('id')->firstOrFail();

        $this->assertNull($secondOrder->user_id);
        $this->assertNotSame($firstOrder->id, $secondOrder->id);
        $this->assertNotSame($firstOrder->cart_id, $secondOrder->cart_id);
    }

    public function test_checkout_token_cannot_reuse_another_customers_paid_order(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $this->stripe->intentStatus = 'succeeded';

        $ownerToken = $this->checkoutToken();
        $this->addCartItem();
        $this->postJson(route('checkout.store'), $this->checkoutPayload($ownerToken))
            ->assertOk()
            ->assertJson(['already_paid' => true]);

        $ownerOrder = Order::firstOrFail();

        $other = User::factory()->create();
        $this->actingAs($other);
        $this->stripe->intentStatus = 'requires_payment_method';
        $this->addCartItem();
        $this->withSession(['checkout_token' => $ownerToken]);

        $response = $this->postJson(route('checkout.store'), $this->checkoutPayload($ownerToken))
            ->assertOk();

        $this->assertArrayNotHasKey('already_paid', $response->json());

        $newOrder = Order::latest('id')->firstOrFail();

        $this->assertSame($other->id, $newOrder->user_id);
        $this->assertNotSame($ownerOrder->id, $newOrder->id);
        $this->assertNotSame($ownerToken, session('checkout_token'));
    }

    public function test_payment_service_rejects_paid_order_for_different_cart_contents(): void
    {
        $this->stripe->intentStatus = 'succeeded';

        $token = $this->checkoutToken();
        $this->addCartItem();
        $this->postJson(route('checkout.store'), $this->checkoutPayload($token))
            ->assertOk()
            ->assertJson(['already_paid' => true]);

        $this->stripe->intentStatus = 'requires_payment_method';
        $this->addCartItem();

        $cart = app(CartService::class)->resolveCart();

        try {
            app(CheckoutPaymentService::class)->start($cart, $this->checkoutPayload($token), null, $token);
            $this->fail('A completed checkout token was reused for different cart contents.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('checkout_token', $exception->errors());
        }
    }

    public function test_account_orders_are_private_to_the_customer(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::create([
            'order_number' => 'SGR-TEST-000001',
            'user_id' => $owner->id,
            'customer_name' => 'Owner',
            'customer_email' => 'owner@example.com',
            'subtotal_cents' => 1000,
            'fulfillment_fee_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1000,
            'fulfillment_method' => 'shipping',
            'order_status' => 'pending_payment',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->actingAs($other)
            ->get(route('account.orders.show', $order))
            ->assertNotFound();
    }
}
