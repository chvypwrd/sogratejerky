<?php

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'name' => \Faker\Factory::create()->randomElement(['Primary Navigation', 'Footer Navigation', 'Sidebar Menu']),
            'location' => \Faker\Factory::create()->unique()->randomElement(['primary', 'footer']),
        ];
    }
}
