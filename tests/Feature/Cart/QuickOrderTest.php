<?php

namespace Tests\Feature\Cart;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function productWithVariant(array $variantAttributes = []): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(array_merge([
            'product_id' => $product->id,
            'price' => 1000,
            'is_active' => true,
        ], $variantAttributes));

        return [$product, $variant];
    }

    public function test_quick_order_shows_only_available_products_and_variants(): void
    {
        [$product, $active] = $this->productWithVariant(['sku' => 'SGR-QO-ACTIVE']);
        $inactiveVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SGR-QO-INACTIVE',
            'is_active' => false,
        ]);

        $inactiveProduct = Product::factory()->inactive()->create(['name' => 'Inactive Quick Product']);
        ProductVariant::factory()->create(['product_id' => $inactiveProduct->id]);

        $this->get(route('quick-order.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee($active->sku)
            ->assertDontSee($inactiveVariant->sku)
            ->assertDontSee($inactiveProduct->name);
    }

    public function test_quick_order_adds_multiple_variants_and_ignores_zero(): void
    {
        [, $first] = $this->productWithVariant(['price' => 1000]);
        [, $second] = $this->productWithVariant(['price' => 1500]);

        $this->post(route('quick-order.store'), [
            'items' => [
                $first->id => 2,
                $second->id => 3,
                '0' => 0,
            ],
        ])->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $first->id, 'quantity' => 2]);
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $second->id, 'quantity' => 3]);
    }

    public function test_quick_order_rejects_invalid_or_inactive_variants_atomically(): void
    {
        [, $valid] = $this->productWithVariant();
        [, $inactive] = $this->productWithVariant(['is_active' => false]);

        $this->post(route('quick-order.store'), [
            'items' => [
                $valid->id => 2,
                $inactive->id => 1,
            ],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_quick_order_rejects_unknown_variants(): void
    {
        [, $valid] = $this->productWithVariant();

        $this->post(route('quick-order.store'), [
            'items' => [
                $valid->id => 1,
                999999 => 1,
            ],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_quick_order_requires_a_selection(): void
    {
        [, $variant] = $this->productWithVariant();

        $this->post(route('quick-order.store'), [
            'items' => [$variant->id => 0],
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_quick_order_consolidates_with_existing_cart(): void
    {
        [$product, $variant] = $this->productWithVariant();

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post(route('quick-order.store'), [
            'items' => [$variant->id => 2],
        ])->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseCount('cart_items', 1);
    }
}
