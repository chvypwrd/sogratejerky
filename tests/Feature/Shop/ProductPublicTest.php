<?php

namespace Tests\Feature\Shop;

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

    private function productJsonLd($response): array
    {
        preg_match('/<script type="application\/ld\+json">(.+?)<\/script>/s', $response->getContent(), $matches);

        $this->assertNotEmpty($matches, 'No product JSON-LD script was found.');

        $data = json_decode($matches[1], true);

        $this->assertIsArray($data, 'Product JSON-LD is not valid JSON.');

        return $data;
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
            'price' => 3499,
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
        $response->assertSee('$34.99');
        $response->assertDontSee($inactive->sku);
        $response->assertSee('value="'.$active->id.'" selected', false);
    }

    public function test_invalid_or_inactive_variant_url_redirects_to_clean_product_url(): void
    {
        $product = Product::factory()->create(['name' => 'Variant Redirect Jerky']);
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);
        $inactive = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => false,
        ]);

        $canonical = route('shop.show', ['product' => $product->slug]);

        $this->get(route('shop.show', ['product' => $product->slug, 'variant' => $inactive->id]))
            ->assertRedirect($canonical);

        $this->get(route('shop.show', ['product' => $product->slug, 'variant' => 999999]))
            ->assertRedirect($canonical);
    }

    public function test_clean_product_url_is_not_rewritten_to_the_default_variant_on_load(): void
    {
        $product = $this->availableProduct(['name' => 'Clean URL Jerky']);

        $response = $this->get(route('shop.show', ['product' => $product->slug]));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'window.history.replaceState'));
        $response->assertDontSee("url.searchParams.get('variant') !== select.value", false);
    }

    public function test_product_canonical_and_sitemap_use_the_configured_hostname(): void
    {
        config(['app.url' => 'https://shop.example.test']);

        $product = $this->availableProduct(['name' => 'Canonical Host Jerky']);
        $variant = $product->availableVariants()->firstOrFail();

        $canonical = 'https://shop.example.test/shop/'.$product->slug;

        $parentResponse = $this->get(route('shop.show', ['product' => $product->slug]));
        $parentResponse->assertOk();
        preg_match('/<link rel="canonical" href="([^"]+)"/', $parentResponse->getContent(), $parentMatches);
        $this->assertSame($canonical, $parentMatches[1] ?? null);

        $variantResponse = $this->get(route('shop.show', [
            'product' => $product->slug,
            'variant' => $variant->id,
        ]));

        $variantResponse->assertOk();
        preg_match('/<link rel="canonical" href="([^"]+)"/', $variantResponse->getContent(), $variantMatches);
        $this->assertSame($canonical, $variantMatches[1] ?? null);

        $jsonLd = $this->productJsonLd($variantResponse);
        $this->assertSame(
            'https://shop.example.test/shop/'.$product->slug.'?variant='.$variant->id,
            $jsonLd['offers']['url']
        );

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('https://shop.example.test/shop/'.$product->slug, false)
            ->assertDontSee('https://shop.example.test/shop/'.$product->slug.'?variant=', false);
    }

    public function test_product_group_variant_markup_includes_size_descriptions_and_no_null_images(): void
    {
        $product = Product::factory()->create([
            'name' => 'Structured Group Jerky',
            'short_description' => 'Structured group description.',
        ]);
        $small = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '3 oz Bag',
            'sku' => 'SGR-GROUP-SMALL',
            'price' => 1499,
            'weight' => 85,
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $large = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '6 Pack',
            'sku' => 'SGR-GROUP-LARGE',
            'price' => 7499,
            'weight' => 510,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $data = $this->productJsonLd(
            $this->get(route('shop.show', ['product' => $product->slug]))
        );

        $this->assertSame('ProductGroup', $data['@type']);
        $this->assertSame($product->slug, $data['productGroupID']);
        $this->assertSame('https://schema.org/size', $data['variesBy']);
        $this->assertArrayNotHasKey('image', $data);
        $this->assertCount(2, $data['hasVariant']);

        $smallData = $data['hasVariant'][0];
        $largeData = $data['hasVariant'][1];

        $this->assertSame('3 oz Bag', $smallData['size']);
        $this->assertSame('SGR-GROUP-SMALL', $smallData['sku']);
        $this->assertStringContainsString('3 oz Bag', $smallData['description']);
        $this->assertStringContainsString('Structured group description.', $smallData['description']);
        $this->assertArrayNotHasKey('image', $smallData);
        $this->assertStringContainsString('?variant='.$small->id, $smallData['offers']['url']);
        $this->assertSame('14.99', $smallData['offers']['price']);
        $this->assertSame('USD', $smallData['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $smallData['offers']['availability']);
        $this->assertSame('SGR-GROUP-SMALL', $smallData['offers']['sku']);

        $this->assertSame('6 Pack', $largeData['size']);
        $this->assertSame('SGR-GROUP-LARGE', $largeData['sku']);
        $this->assertStringContainsString('?variant='.$large->id, $largeData['offers']['url']);
        $this->assertSame('74.99', $largeData['offers']['price']);
        $this->assertArrayNotHasKey('image', $largeData);
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
        $response->assertSee('Available');
        $response->assertSee('Add to Cart');
        $response->assertSee(route('shop.show', ['product' => $product->slug]), false);
    }

    public function test_product_uses_safe_description_fallback_when_content_is_missing(): void
    {
        $product = Product::factory()->create([
            'name' => 'Bare Jerky',
            'short_description' => null,
            'description' => null,
            'seo_description' => null,
        ]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'is_active' => true]);

        $this->get(route('shop.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee('Shop Bare Jerky from Sograte');
    }

    public function test_product_page_emits_one_meta_description_and_product_group_schema(): void
    {
        $product = Product::factory()->create(['name' => 'Grouped Jerky', 'short_description' => 'Grouped description.']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'name' => 'Small', 'is_active' => true]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'name' => 'Large', 'is_active' => true]);

        $response = $this->get(route('shop.show', ['product' => $product->slug]));

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'name="description"'));
        $response->assertSee('"@type":"ProductGroup"', false);
        $response->assertSee('"@type":"Brand"', false);
        $response->assertSee('InStock', false);
    }

    public function test_product_schema_reflects_disabled_checkout_availability(): void
    {
        $product = $this->availableProduct(['name' => 'Disabled Checkout Jerky']);
        config(['commerce.checkout_enabled' => false]);

        $this->get(route('shop.show', ['product' => $product->slug]))
            ->assertOk()
            ->assertSee('OutOfStock', false)
            ->assertSee('Unavailable for online purchase')
            ->assertDontSee('Add to Cart');
    }

    public function test_sitemap_includes_product_tags_with_catalog_products(): void
    {
        $tag = ProductTag::factory()->create();
        $product = $this->availableProduct(['name' => 'Tagged Sitemap Jerky']);
        $product->tags()->attach($tag);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('/shop/tag/'.$tag->slug, false);
    }

    public function test_shop_pagination_has_self_canonical(): void
    {
        for ($i = 0; $i < 13; $i++) {
            $this->availableProduct();
        }

        $this->get(route('shop.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('rel="canonical" href="'.route('shop.index').'?page=2"', false);
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
