<?php

namespace Database\Factories;

use App\Enums\BlogCommentStatus;
use App\Models\BlogComment;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlogComment>
 */
class BlogCommentFactory extends Factory
{
    protected $model = BlogComment::class;

    public function definition(): array
    {
        return [
            'blog_post_id' => BlogPost::factory(),
            'name' => \Faker\Factory::create()->name(),
            'email' => \Faker\Factory::create()->safeEmail(),
            'content' => \Faker\Factory::create()->paragraph(2),
            'status' => BlogCommentStatus::Pending,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => BlogCommentStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => BlogCommentStatus::Rejected,
            'approved_at' => null,
        ]);
    }
}
