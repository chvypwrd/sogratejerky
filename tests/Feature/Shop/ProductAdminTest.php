<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    public function test_administrator_can_create_product_with_variants_and_taxonomy(): void
    {
        $admin = $this->administrator();
        $category = ProductCategory::factory()->create();
        $tag = ProductTag::factory()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'name' => 'Smoked Original',
            'short_description' => 'Bold, smoky, and made in Texas.',
            'description' => '<p>Small-batch beef jerky.</p>',
            'is_active' => 1,
            'is_featured' => 1,
            'sort_order' => 2,
            'categories' => [$category->id],
            'tags' => [$tag->id],
        ])->assertRedirect();

        $product = Product::where('slug', 'smoked-original')->firstOrFail();

        $this->assertTrue($product->is_active);
        $this->assertTrue($product->is_featured);
        $this->assertTrue($product->categories->contains($category));
        $this->assertTrue($product->tags->contains($tag));

        $this->actingAs($admin)->post(route('admin.products.variants.store', $product), [
            'name' => '2 oz Bag',
            'sku' => 'SGR-SMOKE-2',
            'price' => '12.50',
            'weight' => 56,
            'is_active' => 1,
            'sort_order' => 1,
        ])->assertRedirect();

        $variant = ProductVariant::where('sku', 'SGR-SMOKE-2')->firstOrFail();

        $this->assertSame($product->id, $variant->product_id);
        $this->assertSame(1250, $variant->price);
        $this->assertSame(56, $variant->weight);
        $this->assertTrue($variant->is_active);
    }

    public function test_editor_can_create_but_cannot_delete_products(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $this->actingAs($editor)->post(route('admin.products.store'), [
            'name' => 'Editor Product',
            'is_active' => 1,
        ])->assertRedirect();

        $product = Product::where('slug', 'editor-product')->firstOrFail();

        $this->actingAs($editor)->delete(route('admin.products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_guests_and_users_without_product_permission_are_blocked(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_duplicate_sku_and_invalid_price_are_rejected(): void
    {
        $admin = $this->administrator();
        $product = Product::factory()->create();
        ProductVariant::factory()->create(['sku' => 'SGR-DUPLICATE']);

        $this->actingAs($admin)->post(route('admin.products.variants.store', $product), [
            'name' => 'Duplicate SKU',
            'sku' => 'SGR-DUPLICATE',
            'price' => '10.00',
            'weight' => 56,
            'is_active' => 1,
        ])->assertSessionHasErrors('sku');

        $this->actingAs($admin)->post(route('admin.products.variants.store', $product), [
            'name' => 'Free Jerky',
            'sku' => 'SGR-FREE',
            'price' => '0',
            'weight' => 56,
            'is_active' => 1,
        ])->assertSessionHasErrors('price');
    }

    public function test_administrator_can_manage_product_categories_and_tags(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('admin.product-categories.store'), [
            'name' => 'Jerky',
            'description' => 'Beef jerky products.',
            'is_active' => 1,
            'sort_order' => 1,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.product-tags.store'), [
            'name' => 'Spicy',
        ])->assertRedirect();

        $this->assertDatabaseHas('product_categories', ['name' => 'Jerky']);
        $this->assertDatabaseHas('product_tags', ['name' => 'Spicy']);
    }
}
