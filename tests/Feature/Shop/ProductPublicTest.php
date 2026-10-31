<?php

namespace Tests\Feature\Shop;

use App\Enums\BlogPostStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\ProductVariant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductPublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    private function availableProduct(array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        return $product;
    }

    public function test_catalog_only_shows_active_products_with_active_variants(): void
    {
        $visible = $this->availableProduct(['name' => 'Visible Jerky']);

        $inactiveProduct = $this->availableProduct(['name' => 'Inactive Jerky', 'is_active' => false]);

        $inactiveVariantProduct = Product::factory()->create(['name' => 'Inactive Variant Jerky']);
        ProductVariant::factory()->create([
            'product_id' => $inactiveVariantProduct->id,
            'is_active' => false,
        ]);

        $emptyProduct = Product::factory()->create(['name' => 'Empty Jerky']);

        $response = $this->get(route('shop.index'));

        $response->assertOk();
        $response->assertSee($visible->name);
        $response->assertDontSee($inactiveProduct->name);
        $response->assertDontSee($inactiveVariantProduct->name);
        $response->assertDontSee($emptyProduct->name);
    }

    public function test_product_detail_selects_variant_from_shareable_url(): void
    {
        $product = Product::factory()->create(['name' => 'Variant Jerky']);
        $active = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Active Size',
            'sku' => 'SGR-ACTIVE-1',
            'is_active' => true,
        ]);
        $inactive = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Inactive Size',
            'sku' => 'SGR-INACTIVE-1',
            'is_active' => false,
        ]);

        $response = $this->get(route('shop.show', [
            'product' => $product->slug,
            'variant' => $active->id,
        ]));

        $response->assertOk();
        $response->assertSee($product->name);
        $response->assertSee($active->sku);
        $response->assertDontSee($inactive->sku);
        $response->assertSee('value="'.$active->id.'" selected', false);
    }

    public function test_inactive_product_direct_url_returns_not_found(): void
    {
        $product = Product::factory()->inactive()->create();
        ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->get(route('shop.show', ['product' => $product->slug]))->assertNotFound();
    }

    public function test_product_without_active_variants_shows_unavailable(): void
    {
        $product = Product::factory()->create(['name' => 'Unavailable Jerky']);

        $this->get(route('shop.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee('currently unavailable');
    }

    public function test_category_and_tag_archives_show_only_catalog_products(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Snack Sticks']);
        $tag = ProductTag::factory()->create(['name' => 'Smoky']);

        $visible = $this->availableProduct(['name' => 'Archive Visible Jerky']);
        $visible->categories()->attach($category);
        $visible->tags()->attach($tag);

        $hidden = Product::factory()->create(['name' => 'Archive Hidden Jerky']);
        $hidden->categories()->attach($category);
        $hidden->tags()->attach($tag);

        $this->get(route('shop.category', $category->slug))
            ->assertOk()
            ->assertSee($visible->name)
            ->assertDontSee($hidden->name);

        $this->get(route('shop.tag', $tag->slug))
            ->assertOk()
            ->assertSee($visible->name)
            ->assertDontSee($hidden->name);
    }

    public function test_inactive_category_archive_returns_not_found(): void
    {
        $category = ProductCategory::factory()->inactive()->create();

        $this->get(route('shop.category', $category->slug))->assertNotFound();
    }

    public function test_homepage_shows_eligible_featured_products_only(): void
    {
        $featured = $this->availableProduct([
            'name' => 'Featured Jerky',
            'is_featured' => true,
        ]);

        $inactiveFeatured = $this->availableProduct([
            'name' => 'Inactive Featured Jerky',
            'is_featured' => true,
            'is_active' => false,
        ]);

        $regular = $this->availableProduct(['name' => 'Regular Jerky']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee($featured->name);
        $response->assertDontSee($inactiveFeatured->name);
        $response->assertDontSee($regular->name);
    }

    public function test_product_seo_fallbacks_and_structured_data_work(): void
    {
        $product = Product::factory()->create([
            'name' => 'SEO Jerky',
            'short_description' => 'SEO product description.',
            'seo_title' => null,
            'seo_description' => null,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 1499,
            'is_active' => true,
        ]);

        $response = $this->get(route('shop.show', ['product' => $product->slug]));

        $response->assertOk();
        $response->assertSee('SEO Jerky');
        $response->assertSee('SEO product description.');
        $response->assertSee('priceCurrency', false);
        $response->assertSee('InStock', false);
        $response->assertSee(route('shop.show', ['product' => $product->slug]), false);
    }

    public function test_sitemap_includes_active_products_and_non_empty_active_categories(): void
    {
        $category = ProductCategory::factory()->create();
        $product = $this->availableProduct(['name' => 'Sitemap Jerky']);
        $product->categories()->attach($category);

        $inactive = Product::factory()->inactive()->create(['name' => 'Sitemap Inactive Jerky']);
        ProductVariant::factory()->create(['product_id' => $inactive->id]);

        $emptyCategory = ProductCategory::factory()->create();

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertSee('/shop/'.$product->slug, false);
        $response->assertSee('/shop/category/'.$category->slug, false);
        $response->assertDontSee('/shop/'.$inactive->slug, false);
        $response->assertDontSee('/shop/category/'.$emptyCategory->slug, false);
    }
}
