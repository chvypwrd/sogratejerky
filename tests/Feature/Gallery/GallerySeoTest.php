<?php

namespace Tests\Feature\Gallery;

use App\Models\Gallery;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GallerySeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gallery_detail_has_title(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'seo-title-gallery',
            'meta_title' => 'Custom SEO Title for Gallery',
        ]);

        $response = $this->get(route('gallery.show', 'seo-title-gallery'));
        $response->assertOk();
        $response->assertSee('Custom SEO Title for Gallery');
    }

    public function test_gallery_detail_has_canonical_url(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'seo-canonical-gallery',
        ]);

        $response = $this->get(route('gallery.show', 'seo-canonical-gallery'));
        $response->assertOk();
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('/gallery/seo-canonical-gallery', false);
    }

    public function test_gallery_detail_has_og_metadata(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'seo-og-gallery',
            'meta_title' => 'OG Title Gallery',
            'meta_description' => 'OG description for this gallery.',
        ]);

        $response = $this->get(route('gallery.show', 'seo-og-gallery'));
        $response->assertOk();
        $response->assertSee('og:type', false);
        $response->assertSee('og:title', false);
        $response->assertSee('OG Title Gallery', false);
        $response->assertSee('og:description', false);
        $response->assertSee('og:url', false);
    }

    public function test_gallery_detail_has_breadcrumb_json_ld(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'seo-breadcrumb-gallery',
            'title' => 'Breadcrumb Gallery',
        ]);

        $response = $this->get(route('gallery.show', 'seo-breadcrumb-gallery'));
        $response->assertOk();
        $response->assertSee('BreadcrumbList', false);
        $response->assertSee('itemListElement', false);
    }

    public function test_gallery_included_in_sitemap(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'sitemap-gallery',
        ]);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertSee('/gallery/sitemap-gallery', false);
    }

    public function test_inactive_gallery_excluded_from_sitemap(): void
    {
        Gallery::factory()->inactive()->create([
            'slug' => 'inactive-sitemap-gallery',
        ]);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertDontSee('/gallery/inactive-sitemap-gallery', false);
    }
}
