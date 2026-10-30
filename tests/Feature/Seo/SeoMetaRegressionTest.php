<?php

namespace Tests\Feature\Seo;

use App\Models\Page;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetaRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_homepage_has_title(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<title>', false);
        $response->assertSee('</title>', false);
    }

    public function test_homepage_has_description(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
    }

    public function test_homepage_has_og_type(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('og:type', false);
        $response->assertSee('content="website"', false);
    }

    public function test_homepage_has_og_title(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('og:title', false);
    }

    public function test_page_has_correct_title_fallback(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'meta_title' => null,
            'title' => 'SEO Regression Page Title',
        ]);

        $response = $this->get(route('pages.show', $page->slug));

        $response->assertOk();
        $response->assertSee('<title>', false);
        $response->assertSee('SEO Regression Page Title', false);
    }
}
