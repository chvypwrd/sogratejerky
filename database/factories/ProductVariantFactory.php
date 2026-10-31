<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => \Faker\Factory::create()->randomElement(['Original', 'Peppered', 'Teriyaki', 'Habanero']),
            'sku' => 'SGR-'.\Faker\Factory::create()->unique()->bothify('????####'),
            'price' => \Faker\Factory::create()->numberBetween(800, 3000),
            'weight' => \Faker\Factory::create()->randomElement([56, 113, 170, 227]),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
