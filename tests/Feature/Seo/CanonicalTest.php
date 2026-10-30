<?php

namespace Tests\Feature\Seo;

use App\Models\ClassOffering;
use App\Models\Instructor;
use App\Models\Page;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonicalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_homepage_has_canonical_url(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('rel="canonical"', false);
    }

    public function test_published_page_has_canonical_url(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'slug' => 'canonical-test-page',
        ]);

        $response = $this->get(route('pages.show', 'canonical-test-page'));

        $response->assertOk();
        $response->assertSee('rel="canonical"', false);
        $response->assertSee(route('pages.show', 'canonical-test-page', false), false);
    }

    public function test_class_detail_has_canonical_url(): void
    {
        $class = ClassOffering::factory()->active()->create([
            'slug' => 'canonical-test-class',
        ]);

        $response = $this->get(route('classes.show', 'canonical-test-class'));

        $response->assertOk();
        $response->assertSee('rel="canonical"', false);
        $response->assertSee(route('classes.show', 'canonical-test-class', false), false);
    }

    public function test_instructor_detail_has_canonical_url(): void
    {
        $instructor = Instructor::factory()->active()->create([
            'slug' => 'canonical-test-instructor',
        ]);

        $response = $this->get(route('instructors.show', 'canonical-test-instructor'));

        $response->assertOk();
        $response->assertSee('rel="canonical"', false);
        $response->assertSee(route('instructors.show', 'canonical-test-instructor', false), false);
    }

    public function test_faq_page_has_canonical_url(): void
    {
        $response = $this->get(route('faq.index'));

        $response->assertOk();
        $response->assertSee('rel="canonical"', false);
    }

    public function test_contact_page_has_canonical_url(): void
    {
        $response = $this->get(route('contact.show'));

        $response->assertOk();
        $response->assertSee('rel="canonical"', false);
    }

    public function test_explicit_canonical_url_override_works(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'slug' => 'canonical-override-page',
            'canonical_url' => 'https://example.com/custom-canonical',
        ]);

        $response = $this->get(route('pages.show', 'canonical-override-page'));

        $response->assertOk();
        $response->assertSee('https://example.com/custom-canonical', false);
        $response->assertDontSee(route('pages.show', 'canonical-override-page', false), false);
    }
}
