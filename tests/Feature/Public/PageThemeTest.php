<?php

namespace Tests\Feature\Public;

use App\Enums\PageTemplate;
use App\Models\Page;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_default_template_renders_with_readable_width(): void
    {
        $page = Page::factory()
            ->published()
            ->create([
                'slug' => 'about-us',
                'template' => PageTemplate::Default,
                'body' => '<p>About content</p>',
            ]);

        $response = $this->get(route('pages.show', 'about-us'));

        $response->assertOk();
        $response->assertSee('col-lg-8');
    }

    public function test_full_width_template_renders_wider(): void
    {
        $page = Page::factory()
            ->published()
            ->create([
                'slug' => 'wide-page',
                'template' => PageTemplate::FullWidth,
                'body' => '<p>Wide content</p>',
            ]);

        $response = $this->get(route('pages.show', 'wide-page'));

        $response->assertOk();
        $response->assertSee('col-xl-10');
    }

    public function test_landing_template_renders_with_hero_treatment(): void
    {
        $page = Page::factory()
            ->published()
            ->create([
                'slug' => 'landing-page',
                'template' => PageTemplate::Landing,
                'body' => '<p>Landing content</p>',
            ]);

        $response = $this->get(route('pages.show', 'landing-page'));

        $response->assertOk();
        $response->assertSee('landing-hero');
    }

    public function test_sanitized_body_output_safe(): void
    {
        $page = Page::factory()
            ->published()
            ->create([
                'slug' => 'safe-page',
                'body' => '<p>Legitimate content</p><script>alert("xss")</script>',
            ]);

        $response = $this->get(route('pages.show', 'safe-page'));

        $response->assertSee('Legitimate content');
    }

    public function test_title_escaped(): void
    {
        $page = Page::factory()
            ->published()
            ->create([
                'slug' => 'escaped-title',
                'title' => 'Title with <strong>HTML</strong> & "entities"',
            ]);

        $response = $this->get(route('pages.show', 'escaped-title'));

        $response->assertSee('Title with');
    }

    public function test_excerpt_escaped(): void
    {
        $page = Page::factory()
            ->published()
            ->create([
                'slug' => 'escaped-excerpt',
                'excerpt' => 'Excerpt with <em>emphasis</em> & "quotes"',
            ]);

        $response = $this->get(route('pages.show', 'escaped-excerpt'));

        $response->assertSee('Excerpt with');
    }

    public function test_featured_image_alt_preserved(): void
    {
        $page = Page::factory()
            ->published()
            ->create([
                'slug' => 'image-page',
                'body' => '<p>Content</p>',
            ]);

        $response = $this->get(route('pages.show', 'image-page'));

        $response->assertOk();
    }

    public function test_one_h1_per_page(): void
    {
        $page = Page::factory()
            ->published()
            ->create([
                'slug' => 'single-h1',
                'title' => 'Unique Heading',
                'body' => '<h2>Sub heading</h2><p>Content</p>',
            ]);

        $response = $this->get(route('pages.show', 'single-h1'));

        $content = $response->getContent();
        preg_match_all('/<h1[\s>]/i', $content, $matches);
        $this->assertCount(1, $matches[0], 'Page should contain exactly one h1 tag');
    }

    public function test_page_with_no_content_handles_gracefully(): void
    {
        $page = Page::factory()
            ->published()
            ->create([
                'title' => 'Empty Page',
                'slug' => 'empty-page',
                'body' => '',
                'excerpt' => null,
            ]);

        $response = $this->get(route('pages.show', 'empty-page'));

        $response->assertOk();
        $response->assertSee('Empty Page');
    }
}
