<?php

namespace Tests\Feature\Blog;

use App\Enums\BlogCommentStatus;
use App\Enums\BlogPostStatus;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    public function test_administrator_can_create_post_with_taxonomy_and_author(): void
    {
        $admin = $this->administrator();
        $author = User::factory()->create(['name' => 'Jane Writer']);
        $category = BlogCategory::factory()->create();
        $tag = BlogTag::factory()->create();

        $this->actingAs($admin)->post(route('admin.blog.posts.store'), [
            'title' => 'Smoke Signals',
            'excerpt' => 'A short excerpt.',
            'content' => '<p>Hello</p><script>alert(1)</script>',
            'author_id' => $author->id,
            'status' => BlogPostStatus::Published->value,
            'comments_enabled' => 1,
            'seo_title' => 'Smoke Signals SEO',
            'seo_description' => 'SEO description.',
            'categories' => [$category->id],
            'tags' => [$tag->id],
        ])->assertRedirect();

        $post = BlogPost::where('slug', 'smoke-signals')->firstOrFail();

        $this->assertSame($author->id, $post->author_id);
        $this->assertSame('Jane Writer', $post->author_name);
        $this->assertSame(BlogPostStatus::Published, $post->status);
        $this->assertStringNotContainsString('<script', $post->content);
        $this->assertTrue($post->categories->contains($category));
        $this->assertTrue($post->tags->contains($tag));

        $this->get(route('blog.show', $post->slug))->assertOk()->assertSee('Smoke Signals');
    }

    public function test_editor_can_create_but_cannot_delete_posts(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $this->actingAs($editor)->post(route('admin.blog.posts.store'), [
            'title' => 'Editor Post',
            'status' => BlogPostStatus::Draft->value,
        ])->assertRedirect();

        $post = BlogPost::where('slug', 'editor-post')->firstOrFail();

        $this->actingAs($editor)->delete(route('admin.blog.posts.destroy', $post))
            ->assertForbidden();

        $this->assertDatabaseHas('blog_posts', ['id' => $post->id]);
    }

    public function test_guests_and_users_without_blog_permission_are_blocked(): void
    {
        $this->get(route('admin.blog.posts.index'))->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.blog.posts.index'))->assertForbidden();
    }

    public function test_scheduled_posts_publish_when_due(): void
    {
        $due = BlogPost::factory()->create([
            'title' => 'Due Post',
            'status' => BlogPostStatus::Scheduled,
            'published_at' => now()->subMinute(),
        ]);

        $future = BlogPost::factory()->create([
            'title' => 'Future Post',
            'status' => BlogPostStatus::Scheduled,
            'published_at' => now()->addDay(),
        ]);

        $this->artisan('blog:publish-scheduled')->assertExitCode(0);

        $this->assertSame(BlogPostStatus::Published, $due->refresh()->status);
        $this->assertSame(BlogPostStatus::Scheduled, $future->refresh()->status);
    }

    public function test_administrator_can_moderate_comments(): void
    {
        $admin = $this->administrator();
        $post = BlogPost::factory()->published()->create();
        $comment = BlogComment::factory()->create(['blog_post_id' => $post->id]);

        $this->actingAs($admin)->post(route('admin.blog.comments.approve', $comment))
            ->assertRedirect();

        $this->assertSame(BlogCommentStatus::Approved, $comment->refresh()->status);
        $this->assertNotNull($comment->approved_at);
    }

    public function test_administrator_can_manage_categories_and_tags(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->post(route('admin.blog.categories.store'), [
            'name' => 'Recipes',
            'description' => 'Recipe articles.',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.blog.tags.store'), [
            'name' => 'Smoked',
        ])->assertRedirect();

        $this->assertDatabaseHas('blog_categories', ['name' => 'Recipes']);
        $this->assertDatabaseHas('blog_tags', ['name' => 'Smoked']);
    }
}
