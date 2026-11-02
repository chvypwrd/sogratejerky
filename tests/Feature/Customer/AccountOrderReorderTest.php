<?php

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountOrderReorderTest extends TestCase
{
    use RefreshDatabase;

    private function orderFor(User $user, array $items): Order
    {
        $order = Order::factory()->paid()->shipping()->create(['user_id' => $user->id]);

        foreach ($items as $item) {
            $order->items()->create($item);
        }

        return $order;
    }

    private function orderItem(ProductVariant $variant, int $quantity = 1): array
    {
        return [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'quantity' => $quantity,
            'unit_price_cents' => $variant->price,
            'line_total_cents' => $variant->price * $quantity,
            'weight_grams' => $variant->weight,
        ];
    }

    public function test_customer_can_reorder_previous_order_items_into_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 1200,
            'is_active' => true,
        ]);
        $order = $this->orderFor($user, [$this->orderItem($variant, 2)]);

        $this->actingAs($user)
            ->post(route('account.orders.reorder', $order))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_customer_cannot_reorder_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 1200,
            'is_active' => true,
        ]);
        $order = $this->orderFor($owner, [$this->orderItem($variant)]);

        $this->actingAs($other)
            ->post(route('account.orders.reorder', $order))
            ->assertNotFound();

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_reorder_skips_unavailable_items_and_adds_available_items(): void
    {
        $user = User::factory()->create();
        $availableProduct = Product::factory()->create();
        $available = ProductVariant::factory()->create([
            'product_id' => $availableProduct->id,
            'price' => 1000,
            'is_active' => true,
        ]);
        $unavailableProduct = Product::factory()->create();
        $unavailable = ProductVariant::factory()->create([
            'product_id' => $unavailableProduct->id,
            'price' => 1000,
            'is_active' => false,
        ]);

        $order = $this->orderFor($user, [
            $this->orderItem($available),
            $this->orderItem($unavailable),
        ]);

        $this->actingAs($user)
            ->post(route('account.orders.reorder', $order))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $available->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('cart_items', [
            'product_variant_id' => $unavailable->id,
        ]);
    }

    public function test_reorder_with_no_available_items_returns_to_order_history(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->inactive()->create([
            'product_id' => $product->id,
            'price' => 1000,
        ]);
        $order = $this->orderFor($user, [$this->orderItem($variant)]);

        $this->actingAs($user)
            ->post(route('account.orders.reorder', $order))
            ->assertRedirect(route('account.orders.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('cart_items', 0);
    }
}
