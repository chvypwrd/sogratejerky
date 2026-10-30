<?php

namespace Tests\Feature\Seo;

use App\Models\ClassOffering;
use App\Models\Instructor;
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

    public function test_class_has_correct_title_fallback(): void
    {
        $class = ClassOffering::factory()->active()->create([
            'slug' => 'seo-regression-class',
            'meta_title' => null,
            'title' => 'SEO Regression Class Title',
        ]);

        $response = $this->get(route('classes.show', 'seo-regression-class'));

        $response->assertOk();
        $response->assertSee('<title>', false);
        $response->assertSee('SEO Regression Class Title', false);
    }

    public function test_instructor_has_correct_title_fallback(): void
    {
        $instructor = Instructor::factory()->active()->create([
            'slug' => 'seo-regression-instructor',
            'meta_title' => null,
            'name' => 'SEO Regression Instructor Name',
        ]);

        $response = $this->get(route('instructors.show', 'seo-regression-instructor'));

        $response->assertOk();
        $response->assertSee('<title>', false);
        $response->assertSee('SEO Regression Instructor Name', false);
    }
}
