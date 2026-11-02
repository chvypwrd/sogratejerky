<?php

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountOrderHistoryTest extends TestCase
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

    private function item(string $productName, string $variantName, int $quantity, ?Product $product = null): array
    {
        return [
            'product_id' => $product?->id,
            'product_variant_id' => null,
            'product_name' => $productName,
            'variant_name' => $variantName,
            'sku' => 'SGR-HISTORY-'.str()->upper(str()->random(6)),
            'quantity' => $quantity,
            'unit_price_cents' => 1000,
            'line_total_cents' => 1000 * $quantity,
            'weight_grams' => 85,
        ];
    }

    public function test_order_history_shows_saved_product_names_variants_and_quantities(): void
    {
        $user = User::factory()->create();
        $this->orderFor($user, [
            $this->item('Original Beef Jerky', '8 oz', 2),
            $this->item('Peppered Beef Jerky', '4 oz', 3),
        ]);

        $this->actingAs($user)
            ->get(route('account.orders.index'))
            ->assertOk()
            ->assertSee('Original Beef Jerky')
            ->assertSee('8 oz')
            ->assertSee('&times; 2', false)
            ->assertSee('Peppered Beef Jerky')
            ->assertSee('4 oz')
            ->assertSee('&times; 3', false);
    }

    public function test_order_history_uses_historical_item_names_after_catalog_changes(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Current Catalog Name']);
        $this->orderFor($user, [
            $this->item('Historical Jerky Name', '8 oz', 1, $product),
        ]);

        $product->update(['name' => 'Renamed Catalog Product']);

        $this->actingAs($user)
            ->get(route('account.orders.index'))
            ->assertOk()
            ->assertSee('Historical Jerky Name')
            ->assertDontSee('Renamed Catalog Product');
    }

    public function test_order_history_summarizes_long_orders_after_first_three_items(): void
    {
        $user = User::factory()->create();
        $this->orderFor($user, [
            $this->item('First Item', '3 oz', 1),
            $this->item('Second Item', '3 oz', 1),
            $this->item('Third Item', '3 oz', 1),
            $this->item('Fourth Item', '3 oz', 1),
            $this->item('Fifth Item', '3 oz', 1),
        ]);

        $this->actingAs($user)
            ->get(route('account.orders.index'))
            ->assertOk()
            ->assertSee('First Item')
            ->assertSee('Second Item')
            ->assertSee('Third Item')
            ->assertDontSee('Fourth Item')
            ->assertSee('+ 2 more items');
    }

    public function test_order_history_shows_fallback_for_legacy_order_without_items(): void
    {
        $user = User::factory()->create();
        $this->orderFor($user, []);

        $this->actingAs($user)
            ->get(route('account.orders.index'))
            ->assertOk()
            ->assertSee('Item details unavailable');
    }

    public function test_customer_cannot_see_another_customers_order_items_in_history(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->orderFor($other, [
            $this->item('Private Other Customer Jerky', '8 oz', 2),
        ]);

        $this->actingAs($user)
            ->get(route('account.orders.index'))
            ->assertOk()
            ->assertDontSee('Private Other Customer Jerky');
    }

    public function test_customer_still_cannot_open_another_customers_order_details(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->orderFor($other, [
            $this->item('Private Order Detail Jerky', '8 oz', 1),
        ]);

        $this->actingAs($user)
            ->get(route('account.orders.show', $order))
            ->assertNotFound();
    }
}
