<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\FulfillmentSetting;
use App\Models\Order;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRateRule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

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

        $this->post(route('checkout.store'), $this->checkoutPayload($token))
            ->assertRedirect();

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
        $this->assertNull(session('checkout_token'));
    }

    public function test_guest_receives_a_signed_order_confirmation(): void
    {
        $token = $this->checkoutToken();
        $this->addCartItem();

        $response = $this->post(route('checkout.store'), $this->checkoutPayload($token));

        $response->assertRedirectContains('/checkout/confirmation/');

        $order = Order::firstOrFail();
        $this->assertNull($order->user_id);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('No payment has been collected');
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

        $this->post(route('checkout.store'), $this->checkoutPayload($token, [
            'fulfillment_method' => 'pickup',
            'pickup_location_id' => $location->id,
            'billing_same_as_fulfillment' => null,
            'billing_recipient_name' => 'Test Buyer',
            'billing_address_line_1' => '456 Oak Avenue',
            'billing_city' => 'Austin',
            'billing_state' => 'TX',
            'billing_postal_code' => '78702',
            'billing_country' => 'United States',
        ]))->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertSame('pickup', $order->fulfillment_method->value);
        $this->assertSame(300, $order->fulfillment_fee_cents);
        $this->assertSame('North Market', $order->pickup_location_snapshot['name']);
        $this->assertSame('456 Oak Avenue', $order->billing_address_snapshot['address_line_1']);
        $this->assertNull($order->fulfillment_address_snapshot);
    }

    public function test_duplicate_checkout_submission_is_idempotent(): void
    {
        $token = $this->checkoutToken();
        $this->addCartItem();

        $payload = $this->checkoutPayload($token);

        $this->post(route('checkout.store'), $payload)->assertRedirect();
        $this->post(route('checkout.store'), $payload)->assertRedirect();

        $this->assertSame(1, Order::count());
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
