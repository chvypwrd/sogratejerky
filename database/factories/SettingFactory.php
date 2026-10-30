<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'group' => \Faker\Factory::create()->randomElement(['general', 'contact', 'social', 'seo', 'appearance']),
            'key' => \Faker\Factory::create()->unique()->slug(),
            'value' => \Faker\Factory::create()->sentence(),
            'type' => 'string',
        ];
    }
}
