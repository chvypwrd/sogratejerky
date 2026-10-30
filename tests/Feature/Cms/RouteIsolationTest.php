<?php

namespace Tests\Feature\Cms;

use App\Models\Gallery;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_classes_does_not_resolve_as_page(): void
    {
        Page::factory()->create([
            'slug' => 'classes',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/classes');
        $response->assertOk();
        $response->assertSee('Our Classes');
    }

    public function test_instructors_does_not_resolve_as_page(): void
    {
        Page::factory()->create([
            'slug' => 'instructors',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/instructors');
        $response->assertOk();
        $response->assertSee('Our Instructors');
    }

    public function test_faq_does_not_resolve_as_page(): void
    {
        Page::factory()->create([
            'slug' => 'faq',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/faq');
        $response->assertOk();
        $response->assertSee('Frequently Asked Questions');
    }

    public function test_about_still_resolves_as_page(): void
    {
        Page::factory()->create([
            'slug' => 'about',
            'title' => 'About Us',
            'body' => '<p>About our gym</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->get('/about')
            ->assertOk()
            ->assertSee('About Us');
    }

    public function test_admin_classes_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.classes.index'))->assertOk();
    }

    public function test_admin_instructors_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.instructors.index'))->assertOk();
    }

    public function test_admin_testimonials_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.testimonials.index'))->assertOk();
    }

    public function test_admin_faqs_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.faqs.index'))->assertOk();
    }

    public function test_gallery_does_not_resolve_as_page(): void
    {
        Page::factory()->create([
            'slug' => 'gallery',
            'title' => 'Gallery Page',
            'body' => '<p>This is a page about galleries</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/gallery');
        $response->assertOk();
        $response->assertDontSee('This is a page about galleries');
    }

    public function test_instructor_slug_does_not_resolve_as_page(): void
    {
        Page::factory()->create([
            'slug' => 'instructors',
            'title' => 'Instructors Page',
            'body' => '<p>This is a page about instructors</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/instructors');
        $response->assertOk();
        $response->assertDontSee('This is a page about instructors');
    }

    public function test_gallery_route_is_before_catch_all(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'test-gallery-route',
        ]);

        $response = $this->get('/gallery/test-gallery-route');
        $response->assertOk();
    }
}
