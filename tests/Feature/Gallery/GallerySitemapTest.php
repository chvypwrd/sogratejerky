<?php

namespace Tests\Feature\Gallery;

use App\Enums\RobotsDirective;
use App\Models\Gallery;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GallerySitemapTest extends TestCase
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

    public function test_gallery_index_url_in_sitemap(): void
    {
        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertSee(config('app.url').'/gallery', false);
    }

    public function test_active_gallery_detail_in_sitemap(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'active-sitemap-detail',
        ]);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertSee('/gallery/active-sitemap-detail', false);
    }

    public function test_inactive_gallery_excluded(): void
    {
        Gallery::factory()->inactive()->create([
            'slug' => 'inactive-sitemap-test',
        ]);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertDontSee('/gallery/inactive-sitemap-test', false);
    }

    public function test_trashed_gallery_excluded(): void
    {
        $gallery = Gallery::factory()->active()->create([
            'slug' => 'trashed-sitemap-test',
        ]);
        $gallery->delete();

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertDontSee('/gallery/trashed-sitemap-test', false);
    }

    public function test_noindex_gallery_excluded(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'noindex-sitemap-test',
            'robots' => RobotsDirective::NoIndexFollow,
        ]);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertDontSee('/gallery/noindex-sitemap-test', false);
    }

    public function test_noindex_follow_gallery_excluded_from_sitemap(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'noindex-follow-excluded',
            'robots' => RobotsDirective::NoIndexFollow,
        ]);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertDontSee('/gallery/noindex-follow-excluded', false);
    }

    public function test_noindex_nofollow_gallery_excluded_from_sitemap(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'noindex-nofollow-excluded',
            'robots' => RobotsDirective::NoIndexNoFollow,
        ]);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertDontSee('/gallery/noindex-nofollow-excluded', false);
    }

    public function test_index_follow_gallery_included_in_sitemap(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'index-follow-included',
            'robots' => RobotsDirective::IndexFollow,
        ]);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertSee('/gallery/index-follow-included', false);
    }
}
