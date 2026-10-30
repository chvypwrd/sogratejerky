<?php

namespace Database\Factories;

use App\Enums\RobotsDirective;
use App\Models\ClassOffering;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClassOffering>
 */
class ClassOfferingFactory extends Factory
{
    protected $model = ClassOffering::class;

    public function definition(): array
    {
        $title = \Faker\Factory::create()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'short_description' => \Faker\Factory::create()->paragraph(1),
            'description' => '<p>'.\Faker\Factory::create()->paragraph(3).'</p>',
            'level' => \Faker\Factory::create()->randomElement(['Beginner', 'Intermediate', 'Advanced', 'All Levels']),
            'duration' => \Faker\Factory::create()->randomElement(['30 min', '45 min', '60 min', '90 min']),
            'featured' => false,
            'active' => true,
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
