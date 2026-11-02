<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryZone;
use App\Models\FulfillmentSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CheckoutCalculator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalDeliveryPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        FulfillmentSetting::current()->update([
            'shipping_enabled' => true,
            'local_delivery_enabled' => true,
            'pickup_enabled' => false,
            'shipping_basis' => 'subtotal',
            'local_delivery_threshold_enabled' => true,
            'local_delivery_threshold_cents' => 5000,
            'local_delivery_flat_fee_cents' => 500,
        ]);

        DeliveryZone::create([
            'name' => 'Austin',
            'zip_code' => '78701',
            'fee_cents' => 700,
            'is_active' => true,
        ]);
    }

    private function calculateForPrice(int $priceCents): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => $priceCents,
            'is_active' => true,
        ]);

        $cart = Cart::create(['guest_token_hash' => hash('sha256', uniqid('', true))]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        return app(CheckoutCalculator::class)->calculate($cart, 'local_delivery', [
            'fulfillment_recipient_name' => 'Test Buyer',
            'fulfillment_address_line_1' => '123 Main Street',
            'fulfillment_city' => 'Austin',
            'fulfillment_state' => 'TX',
            'fulfillment_postal_code' => '78701',
            'fulfillment_country' => 'United States',
            'billing_same_as_fulfillment' => true,
        ], null);
    }

    public function test_threshold_boundaries_use_flat_fee_at_or_below_and_zone_fee_above(): void
    {
        $this->assertSame(500, $this->calculateForPrice(4999)['fulfillment_fee_cents']);
        $this->assertSame(500, $this->calculateForPrice(5000)['fulfillment_fee_cents']);
        $this->assertSame(700, $this->calculateForPrice(5001)['fulfillment_fee_cents']);
    }

    public function test_disabled_override_uses_zone_fee(): void
    {
        FulfillmentSetting::current()->update(['local_delivery_threshold_enabled' => false]);

        $this->assertSame(700, $this->calculateForPrice(2500)['fulfillment_fee_cents']);
    }

    public function test_zero_zone_fee_provides_free_delivery_above_threshold(): void
    {
        DeliveryZone::where('zip_code', '78701')->update(['fee_cents' => 0]);

        $this->assertSame(500, $this->calculateForPrice(5000)['fulfillment_fee_cents']);
        $this->assertSame(0, $this->calculateForPrice(5001)['fulfillment_fee_cents']);
    }
}
