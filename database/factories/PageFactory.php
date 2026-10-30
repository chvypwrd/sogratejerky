<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Enums\RobotsDirective;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = \Faker\Factory::create()->sentence(3);

        return [
            'created_by' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => '<p>'.\Faker\Factory::create()->paragraph(3).'</p>',
            'excerpt' => \Faker\Factory::create()->paragraph(1),
            'template' => PageTemplate::Default,
            'status' => PageStatus::Draft,
            'robots' => RobotsDirective::IndexFollow,
            'published_at' => null,
            'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::Published,
            'published_at' => \Faker\Factory::create()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::Draft,
            'published_at' => null,
        ]);
    }
}
