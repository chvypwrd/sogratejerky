<?php

namespace Tests\Feature\Seo;

use App\Enums\PageStatus;
use App\Enums\RobotsDirective;
use App\Models\ClassOffering;
use App\Models\Instructor;
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

    public function test_classes_index_url_included(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertSee(config('app.url').'/classes', false);
    }

    public function test_instructors_index_url_included(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertSee(config('app.url').'/instructors', false);
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

    public function test_active_class_included(): void
    {
        $class = ClassOffering::factory()->active()->create([
            'slug' => 'active-test-class',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertSee('active-test-class', false);
    }

    public function test_inactive_class_excluded(): void
    {
        $class = ClassOffering::factory()->inactive()->create([
            'slug' => 'inactive-test-class',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertDontSee('inactive-test-class', false);
    }

    public function test_active_instructor_included(): void
    {
        $instructor = Instructor::factory()->active()->create([
            'slug' => 'active-test-instructor',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertSee('active-test-instructor', false);
    }

    public function test_inactive_instructor_excluded(): void
    {
        $instructor = Instructor::factory()->inactive()->create([
            'slug' => 'inactive-test-instructor',
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertDontSee('inactive-test-instructor', false);
    }

    public function test_only_current_slugs_included_old_redirect_slugs_excluded(): void
    {
        $class = ClassOffering::factory()->active()->create([
            'title' => 'Original Title',
            'slug' => 'current-slug',
        ]);

        $oldSlug = $class->slug;

        $class->updateQuietly(['slug' => 'new-slug']);

        $response = $this->get(route('sitemap'));

        $response->assertSee('new-slug', false);
        $response->assertDontSee('current-slug', false);
    }

    public function test_noindex_follow_class_excluded_from_sitemap(): void
    {
        ClassOffering::factory()->active()->create([
            'slug' => 'noindex-follow-class',
            'robots' => RobotsDirective::NoIndexFollow,
        ]);

        $response = $this->get(route('sitemap'));
        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $xml->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertEmpty(
            $xml->xpath("//sm:url[contains(sm:loc, 'noindex-follow-class')]"),
            'noindex,follow class should not appear in sitemap'
        );
    }

    public function test_noindex_nofollow_class_excluded_from_sitemap(): void
    {
        ClassOffering::factory()->active()->create([
            'slug' => 'noindex-nofollow-class',
            'robots' => RobotsDirective::NoIndexNoFollow,
        ]);

        $response = $this->get(route('sitemap'));
        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $xml->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertEmpty(
            $xml->xpath("//sm:url[contains(sm:loc, 'noindex-nofollow-class')]"),
            'noindex,nofollow class should not appear in sitemap'
        );
    }

    public function test_active_indexable_class_included_in_sitemap(): void
    {
        ClassOffering::factory()->active()->create([
            'slug' => 'indexable-class',
            'robots' => RobotsDirective::IndexFollow,
        ]);

        $response = $this->get(route('sitemap'));
        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $xml->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertNotEmpty(
            $xml->xpath("//sm:url[contains(sm:loc, 'indexable-class')]"),
            'indexable class should appear in sitemap'
        );
    }

    public function test_noindex_follow_instructor_excluded_from_sitemap(): void
    {
        Instructor::factory()->active()->create([
            'slug' => 'noindex-follow-instructor',
            'robots' => RobotsDirective::NoIndexFollow,
        ]);

        $response = $this->get(route('sitemap'));
        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $xml->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertEmpty(
            $xml->xpath("//sm:url[contains(sm:loc, 'noindex-follow-instructor')]"),
            'noindex,follow instructor should not appear in sitemap'
        );
    }

    public function test_noindex_nofollow_instructor_excluded_from_sitemap(): void
    {
        Instructor::factory()->active()->create([
            'slug' => 'noindex-nofollow-instructor',
            'robots' => RobotsDirective::NoIndexNoFollow,
        ]);

        $response = $this->get(route('sitemap'));
        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $xml->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertEmpty(
            $xml->xpath("//sm:url[contains(sm:loc, 'noindex-nofollow-instructor')]"),
            'noindex,nofollow instructor should not appear in sitemap'
        );
    }

    public function test_active_indexable_instructor_included_in_sitemap(): void
    {
        Instructor::factory()->active()->create([
            'slug' => 'indexable-instructor',
            'robots' => RobotsDirective::IndexFollow,
        ]);

        $response = $this->get(route('sitemap'));
        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $xml->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertNotEmpty(
            $xml->xpath("//sm:url[contains(sm:loc, 'indexable-instructor')]"),
            'indexable instructor should appear in sitemap'
        );
    }
}
