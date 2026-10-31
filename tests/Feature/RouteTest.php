<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_home_returns_200(): void
    {
        $this->get('/')
            ->assertOk();
    }

    public function test_admin_redirects_to_login_unauthenticated(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('login'));
    }

    public function test_admin_login_works(): void
    {
        $this->get(route('login'))
            ->assertOk();
    }

    public function test_admin_security_works_authenticated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->get(route('admin.security'))
            ->assertOk();
    }

    public function test_admin_pages_works_authenticated_with_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.pages.index'))
            ->assertOk();
    }

    public function test_register_route_exists(): void
    {
        $this->get('/register')
            ->assertOk();
    }

    public function test_admin_forgot_password_works(): void
    {
        $this->get(route('password.request'))
            ->assertOk();
    }

    public function test_valid_cms_page_slug_works(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'slug' => 'test-public-page',
        ]);

        $this->get('/test-public-page')
            ->assertOk()
            ->assertSee($page->title);
    }

    public function test_admin_preview_requires_auth(): void
    {
        $page = Page::factory()->create(['created_by' => null]);

        $this->get(route('pages.preview', $page->slug))
            ->assertRedirect(route('login'));
    }

    public function test_classes_route_is_removed(): void
    {
        $this->get('/classes')
            ->assertNotFound();
    }

    public function test_instructors_route_is_removed(): void
    {
        $this->get('/instructors')
            ->assertNotFound();
    }

    public function test_faq_route_works(): void
    {
        $this->get('/faq')
            ->assertOk();
    }

    public function test_admin_menus_works_with_auth_and_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->get(route('admin.menus.index'))
            ->assertOk();
    }

    public function test_admin_settings_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_admin_redirects_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->get(route('admin.redirects.index'))
            ->assertOk();
    }

    public function test_home_still_works(): void
    {
        $this->get('/')
            ->assertOk();
    }

    public function test_customer_registration_route_is_available(): void
    {
        $this->get('/register')
            ->assertOk();
    }
}
