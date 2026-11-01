<?php

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\SograteLegacyJerkySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SograteLegacyJerkySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_imports_ten_products_and_sixteen_inactive_variants_with_legacy_skus(): void
    {
        $this->seed(SograteLegacyJerkySeeder::class);

        $this->assertSame(10, Product::count());
        $this->assertSame(16, ProductVariant::count());
        $this->assertSame(10, Product::where('is_active', false)->count());
        $this->assertSame(16, ProductVariant::where('is_active', false)->count());
        $this->assertSame(0, ProductVariant::where('price', '>', 0)->count());

        $original = Product::where('slug', 'original-flavor-beef-jerky')->firstOrFail();
        $this->assertSame(['10011', '10013'], $original->variants()->orderBy('sort_order')->pluck('sku')->all());
        $this->assertSame(85, $original->variants()->where('sku', '10011')->value('weight'));

        $combo = Product::where('slug', 'beef-jerky-combo-six-pack')->firstOrFail();
        $this->assertSame(['10000'], $combo->variants()->pluck('sku')->all());

        $holyGhost = Product::where('slug', 'holy-ghost-beef-jerky')->firstOrFail();
        $this->assertSame(57, $holyGhost->variants()->where('sku', '10081')->value('weight'));
    }

    public function test_imported_copy_comes_from_the_source_json(): void
    {
        $source = json_decode(file_get_contents(database_path('seeders/data/sograte_product_seed_data.json')), true, 512, JSON_THROW_ON_ERROR);
        $sourceProduct = collect($source['products'])->firstWhere('slug', 'original-flavor-beef-jerky');

        $this->seed(SograteLegacyJerkySeeder::class);

        $product = Product::where('slug', 'original-flavor-beef-jerky')->firstOrFail();

        $this->assertSame($sourceProduct['short_description'], $product->short_description);
        $this->assertSame($sourceProduct['description'], $product->description);
    }

    public function test_repeated_seeding_is_idempotent_and_preserves_administrator_edits(): void
    {
        $this->seed(SograteLegacyJerkySeeder::class);

        $product = Product::where('slug', 'original-flavor-beef-jerky')->firstOrFail();
        $variant = ProductVariant::where('sku', '10011')->firstOrFail();

        $product->update([
            'name' => 'Owner Edited Original',
            'is_active' => true,
        ]);
        $variant->update([
            'name' => 'Owner Edited 3 oz',
            'price' => 999,
            'weight' => 90,
            'is_active' => true,
        ]);

        $this->seed(SograteLegacyJerkySeeder::class);

        $this->assertSame(10, Product::count());
        $this->assertSame(16, ProductVariant::count());
        $this->assertSame('Owner Edited Original', $product->fresh()->name);
        $this->assertTrue($product->fresh()->is_active);
        $this->assertSame('Owner Edited 3 oz', $variant->fresh()->name);
        $this->assertSame(999, $variant->fresh()->price);
        $this->assertSame(90, $variant->fresh()->weight);
        $this->assertTrue($variant->fresh()->is_active);
    }
}
