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
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BlogPublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_blog_index_only_shows_published_posts(): void
    {
        $published = BlogPost::factory()->published()->create(['title' => 'Published Story']);
        $draft = BlogPost::factory()->create(['title' => 'Draft Story']);
        $scheduled = BlogPost::factory()->scheduled()->create(['title' => 'Scheduled Story']);

        $response = $this->get(route('blog.index'));

        $response->assertOk();
        $response->assertSee($published->title);
        $response->assertDontSee($draft->title);
        $response->assertDontSee($scheduled->title);
    }

    public function test_draft_and_scheduled_posts_are_private_at_direct_urls(): void
    {
        $draft = BlogPost::factory()->create(['title' => 'Private Draft']);
        $scheduled = BlogPost::factory()->scheduled()->create(['title' => 'Private Scheduled']);

        $this->get(route('blog.show', $draft->slug))->assertNotFound();
        $this->get(route('blog.show', $scheduled->slug))->assertNotFound();
    }

    public function test_category_and_tag_archives_only_show_published_posts(): void
    {
        $category = BlogCategory::factory()->create();
        $tag = BlogTag::factory()->create();

        $published = BlogPost::factory()->published()->create(['title' => 'Archive Published']);
        $published->categories()->attach($category);
        $published->tags()->attach($tag);

        $draft = BlogPost::factory()->create(['title' => 'Archive Draft']);
        $draft->categories()->attach($category);
        $draft->tags()->attach($tag);

        $this->get(route('blog.category', $category->slug))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title);

        $this->get(route('blog.tag', $tag->slug))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title);
    }

    public function test_search_excludes_unpublished_posts(): void
    {
        $published = BlogPost::factory()->published()->create(['title' => 'Smoky Brisket Guide']);
        $draft = BlogPost::factory()->create(['title' => 'Smoky Draft Guide']);

        $response = $this->get(route('blog.search', ['q' => 'Smoky']));

        $response->assertOk();
        $response->assertSee($published->title);
        $response->assertDontSee($draft->title);
    }

    public function test_only_approved_comments_display_and_output_is_escaped(): void
    {
        $post = BlogPost::factory()->published()->create();

        BlogComment::factory()->approved()->create([
            'blog_post_id' => $post->id,
            'name' => 'Approved Commenter',
            'content' => 'Safe <script>alert(1)</script> comment',
        ]);

        BlogComment::factory()->create([
            'blog_post_id' => $post->id,
            'name' => 'Pending Commenter',
            'content' => 'Pending comment content',
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        $response->assertSee('Approved Commenter');
        $response->assertDontSee('Pending Commenter');
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_guest_comments_are_pending_until_moderated(): void
    {
        $post = BlogPost::factory()->published()->create();

        $this->post(route('blog.comments.store', $post->slug), [
            'name' => 'Guest Commenter',
            'email' => 'guest@example.com',
            'content' => 'This is a thoughtful comment.',
        ])->assertRedirect();

        $this->assertDatabaseHas('blog_comments', [
            'blog_post_id' => $post->id,
            'name' => 'Guest Commenter',
            'status' => BlogCommentStatus::Pending->value,
        ]);
    }

    public function test_comments_cannot_be_submitted_when_disabled(): void
    {
        $post = BlogPost::factory()->published()->create(['comments_enabled' => false]);

        $this->post(route('blog.comments.store', $post->slug), [
            'name' => 'Guest Commenter',
            'email' => 'guest@example.com',
            'content' => 'This should not be accepted.',
        ])->assertNotFound();
    }

    public function test_rss_feed_contains_only_published_posts(): void
    {
        $published = BlogPost::factory()->published()->create(['title' => 'RSS Published Story']);
        $draft = BlogPost::factory()->create(['title' => 'RSS Draft Story']);

        $response = $this->get(route('blog.feed'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $this->assertNotFalse(simplexml_load_string($response->getContent()));
        $response->assertSee($published->title);
        $response->assertDontSee($draft->title);
    }

    public function test_sitemap_includes_only_published_blog_posts(): void
    {
        $published = BlogPost::factory()->published()->create(['slug' => 'sitemap-published-post']);
        $draft = BlogPost::factory()->create(['slug' => 'sitemap-draft-post']);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertSee('/blog/'.$published->slug, false);
        $response->assertDontSee('/blog/'.$draft->slug, false);
    }

    public function test_homepage_shows_latest_published_posts_only(): void
    {
        $published = BlogPost::factory()->published()->create(['title' => 'Homepage Blog Story']);
        $draft = BlogPost::factory()->create(['title' => 'Homepage Draft Story']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee($published->title);
        $response->assertDontSee($draft->title);
    }

    public function test_author_archive_only_shows_published_posts(): void
    {
        $author = User::factory()->create(['name' => 'Public Author']);

        $published = BlogPost::factory()->published()->create([
            'author_id' => $author->id,
            'author_name' => $author->name,
            'title' => 'Author Published Story',
        ]);

        $draft = BlogPost::factory()->create([
            'author_id' => $author->id,
            'author_name' => $author->name,
            'title' => 'Author Draft Story',
        ]);

        $this->get(route('blog.author', $author->id))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title)
            ->assertSee('Public Author');
    }

    public function test_post_seo_fallbacks_and_structured_data_work(): void
    {
        $post = BlogPost::factory()->published()->create([
            'title' => 'SEO Blog Story',
            'excerpt' => 'SEO fallback excerpt.',
            'seo_title' => null,
            'seo_description' => null,
        ]);

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertOk();
        $response->assertSee('SEO Blog Story');
        $response->assertSee('SEO fallback excerpt.');
        $response->assertSee('BlogPosting', false);
        $response->assertSee(route('blog.show', $post->slug), false);
    }
}
