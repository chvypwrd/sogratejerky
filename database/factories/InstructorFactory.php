<?php

namespace Database\Factories;

use App\Enums\RobotsDirective;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Instructor>
 */
class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    public function definition(): array
    {
        $name = \Faker\Factory::create()->name();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'title' => \Faker\Factory::create()->randomElement(['Head Coach', 'Senior Instructor', 'Coach', 'Assistant Instructor']),
            'short_bio' => \Faker\Factory::create()->paragraph(1),
            'bio' => '<p>'.\Faker\Factory::create()->paragraph(3).'</p>',
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
}
