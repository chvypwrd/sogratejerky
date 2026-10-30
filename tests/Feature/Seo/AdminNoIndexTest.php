<?php

namespace Tests\Feature\Seo;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNoIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_login_page_has_x_robots_tag(): void
    {
        $response = $this->get('/admin/login');

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_admin_dashboard_has_x_robots_tag(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_public_homepage_does_not_have_x_robots_tag(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertFalse(
            $response->headers->has('X-Robots-Tag'),
            'Public homepage should not have X-Robots-Tag header'
        );
    }

    public function test_forgot_password_has_x_robots_tag(): void
    {
        $response = $this->get('/admin/forgot-password');

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_login_has_x_robots_tag(): void
    {
        $response = $this->get('/admin/login');

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
