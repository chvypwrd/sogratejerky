<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => \Faker\Factory::create()->sentence(8).'?',
            'answer' => '<p>'.\Faker\Factory::create()->paragraph(2).'</p>',
            'category' => \Faker\Factory::create()->randomElement(['General', 'Classes', 'Pricing', 'Schedule', 'Equipment']),
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
}
