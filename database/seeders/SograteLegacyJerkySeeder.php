<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SograteLegacyJerkySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/sograte_product_seed_data.json');

        if (! is_file($path)) {
            throw new RuntimeException("Legacy jerky seed data is missing: {$path}");
        }

        $catalog = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($catalog): void {
            foreach ($catalog['products'] as $productIndex => $source) {
                $sourceSkus = collect($source['variants'])->pluck('sku')->map(fn ($sku) => (string) $sku);
                $variantsBySku = ProductVariant::query()
                    ->whereIn('sku', $sourceSkus)
                    ->get()
                    ->keyBy('sku');

                $productIds = $variantsBySku->pluck('product_id')->unique()->values();

                if ($productIds->count() > 1) {
                    throw new RuntimeException("Legacy SKUs for {$source['slug']} are split across multiple products.");
                }

                $product = $productIds->count() === 1
                    ? Product::findOrFail($productIds->first())
                    : Product::query()->where('slug', $source['slug'])->first();

                if (! $product) {
                    $product = Product::create([
                        'name' => $source['name'],
                        'slug' => $source['slug'],
                        'short_description' => $source['short_description'],
                        'description' => $source['description'],
                        'is_active' => false,
                        'is_featured' => false,
                        'sort_order' => $productIndex,
                    ]);
                }

                foreach ($source['variants'] as $variantIndex => $variant) {
                    $sku = (string) $variant['sku'];
                    $existing = $variantsBySku->get($sku);

                    if ($existing) {
                        if ((int) $existing->product_id !== (int) $product->getKey()) {
                            throw new RuntimeException("Legacy SKU {$sku} belongs to a different product.");
                        }

                        continue;
                    }

                    ProductVariant::create([
                        'product_id' => $product->getKey(),
                        'name' => $variant['name'],
                        'sku' => $sku,
                        'price' => 0,
                        'weight' => (int) $variant['weight_grams'],
                        'is_active' => false,
                        'sort_order' => $variantIndex,
                    ]);
                }
            }
        });
    }
}
