<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartTest extends TestCase
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
            'price' => 1250,
            'is_active' => true,
        ], $variantAttributes));

        return [$product, $variant];
    }

    public function test_guest_cart_persists_across_requests(): void
    {
        [$product, $variant] = $this->productWithVariant();

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('carts', ['user_id' => null]);
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('$25.00');
    }

    public function test_adding_same_variant_consolidates_quantity(): void
    {
        [$product, $variant] = $this->productWithVariant();

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertRedirect();

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $variant->id, 'quantity' => 5]);
    }

    public function test_cart_quantities_can_be_updated_and_items_removed(): void
    {
        [$product, $variant] = $this->productWithVariant();

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $item = CartItem::firstOrFail();

        $this->patch(route('cart.items.update', $item), ['quantity' => 4])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 4]);

        $this->delete(route('cart.items.destroy', $item))
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_inactive_variants_are_rejected(): void
    {
        [$product, $variant] = $this->productWithVariant(['is_active' => false]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_subtotal_uses_current_variant_price(): void
    {
        [$product, $variant] = $this->productWithVariant(['price' => 1000]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $this->get(route('cart.index'))->assertSee('$20.00');

        $variant->update(['price' => 1200]);

        $this->get(route('cart.index'))->assertSee('$24.00');
    }

    public function test_authenticated_carts_are_isolated(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        [$productA, $variantA] = $this->productWithVariant();
        [$productB, $variantB] = $this->productWithVariant();

        $this->actingAs($userA)->post(route('cart.items.store'), [
            'product_id' => $productA->id,
            'product_variant_id' => $variantA->id,
            'quantity' => 1,
        ]);

        $this->actingAs($userB)->post(route('cart.items.store'), [
            'product_id' => $productB->id,
            'product_variant_id' => $variantB->id,
            'quantity' => 1,
        ]);

        $this->actingAs($userA)->get(route('cart.index'))
            ->assertSee($productA->name)
            ->assertDontSee($productB->name);

        $itemB = CartItem::whereHas('cart', fn ($query) => $query->where('user_id', $userB->id))->firstOrFail();

        $this->actingAs($userA)->patch(route('cart.items.update', $itemB), ['quantity' => 2])
            ->assertNotFound();
    }

    public function test_guest_cart_merges_into_user_cart_once_on_login(): void
    {
        $user = User::factory()->create();
        [$product, $variant] = $this->productWithVariant();

        $token = Str::random(64);
        $this->withSession(['sograte_cart_token' => $token]);

        $guestCart = app(CartService::class)->resolveCart();
        app(CartService::class)->addVariantToCart($guestCart, $variant, 2);

        [, $secondVariant] = $this->productWithVariant(['price' => 900]);
        app(CartService::class)->addVariantToCart($guestCart, $secondVariant, 1);

        $userCart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $userCart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        event(new Login('web', $user, false));

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_variant_id' => $secondVariant->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);

        event(new Login('web', $user, false));

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_unavailable_items_are_visible_but_excluded_from_subtotal(): void
    {
        [$product, $variant] = $this->productWithVariant(['price' => 1000]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $variant->update(['is_active' => false]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('This item is no longer available')
            ->assertSee('$0.00')
            ->assertSee('Checkout Unavailable')
            ->assertDontSee('Proceed to Checkout');
    }

    public function test_quantity_limit_applies_to_accumulated_quantity(): void
    {
        [$product, $variant] = $this->productWithVariant();
        $max = config('cart.max_quantity');

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => $max,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'quantity' => $max,
        ]);
    }
}
