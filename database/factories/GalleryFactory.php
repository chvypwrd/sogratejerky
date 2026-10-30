<?php

namespace Database\Factories;

use App\Enums\RobotsDirective;
use App\Models\Gallery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    protected $model = Gallery::class;

    public function definition(): array
    {
        $title = \Faker\Factory::create()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => \Faker\Factory::create()->paragraph(3),
            'active' => true,
            'featured' => false,
            'sort_order' => 0,
            'robots' => RobotsDirective::IndexFollow,
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'active' => false,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => [
            'featured' => true,
        ]);
    }
}
