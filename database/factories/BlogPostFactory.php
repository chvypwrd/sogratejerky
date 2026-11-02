<?php

namespace Database\Factories;

use App\Enums\BlogPostStatus;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = \Faker\Factory::create()->sentence(5);

        return [
            'author_id' => null,
            'author_name' => 'Sograte',
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => \Faker\Factory::create()->paragraph(1),
            'content' => '<p>'.\Faker\Factory::create()->paragraph(4).'</p>',
            'status' => BlogPostStatus::Draft,
            'published_at' => null,
            'comments_enabled' => true,
            'seo_title' => null,
            'seo_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => BlogPostStatus::Published,
            'published_at' => \Faker\Factory::create()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => BlogPostStatus::Scheduled,
            'published_at' => \Faker\Factory::create()->dateTimeBetween('now', '+1 month'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => BlogPostStatus::Draft,
            'published_at' => null,
        ]);
    }
}
