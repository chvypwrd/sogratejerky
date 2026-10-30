<?php

namespace Database\Factories;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'name' => \Faker\Factory::create()->name(),
            'quote' => \Faker\Factory::create()->paragraph(1),
            'attribution' => \Faker\Factory::create()->randomElement(['Parent', 'Student', 'Member', 'Athlete']),
            'featured' => false,
            'active' => true,
            'sort_order' => 0,
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
