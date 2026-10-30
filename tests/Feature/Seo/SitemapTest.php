<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Enums\RobotsDirective;
use App\Models\Page;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SitemapTest extends TestCase
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

    public function test_sitemap_returns_200(): void
    {
        $this->get(route('sitemap'))->assertOk();
    }

    public function test_content_type_is_application_xml(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_response_contains_valid_xml(): void
    {
        $response = $this->get(route('sitemap'));
        $content = $response->getContent();

        $xml = simplexml_load_string($content);

        $this->assertNotFalse($xml, 'Sitemap response is not valid XML');
        $this->assertEquals('urlset', $xml->getName());
    }

    public function test_homepage_url_included(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertSee(config('app.url'), false);
    }

    public function test_faq_url_included(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertSee(config('app.url').'/faq', false);
    }

    public function test_contact_url_included(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertSee(config('app.url').'/contact', false);
    }

    public function test_published_page_included(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'slug' => 'test-published-page',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertSee($page->slug, false);
    }

    public function test_draft_page_excluded(): void
    {
        $page = Page::factory()->draft()->create([
            'created_by' => null,
            'slug' => 'test-draft-page',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertDontSee($page->slug, false);
    }

    public function test_future_published_page_excluded(): void
    {
        $page = Page::factory()->create([
            'created_by' => null,
            'slug' => 'test-future-page',
            'status' => PageStatus::Published,
            'published_at' => now()->addDays(30),
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertDontSee($page->slug, false);
    }

    public function test_deleted_page_excluded(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'slug' => 'test-deleted-page',
        ]);
        $page->delete();

        $response = $this->get(route('sitemap'));

        $response->assertDontSee('test-deleted-page', false);
    }

    public function test_noindex_page_excluded(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'slug' => 'test-noindex-page',
            'robots' => RobotsDirective::NoIndexNoFollow,
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertDontSee('test-noindex-page', false);
    }

    public function test_obsolete_class_and_instructor_routes_excluded(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertDontSee(config('app.url').'/classes', false);
        $response->assertDontSee(config('app.url').'/instructors', false);
    }
}
