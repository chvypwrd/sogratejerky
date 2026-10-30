<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
    }

    public function test_guest_cannot_access_admin(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_public_homepage_remains_accessible(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    public function test_administrator_can_see_login_form(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Admin Login');
    }

    public function test_valid_credentials_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_valid_login_redirects_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_invalid_password_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_unknown_email_fails_safely(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123456',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_session_is_authenticated_after_valid_login(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $this->assertAuthenticated();
    }

    public function test_already_authenticated_admin_does_not_see_login_form(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('login'));

        $response->assertRedirect();
    }

    public function test_registration_route_does_not_exist(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_registration_post_endpoint_does_not_exist(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123456',
            'password_confirmation' => 'password123456',
        ]);

        $response->assertStatus(404);
    }

    public function test_repeated_failed_login_attempts_trigger_throttling(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login'), [
                'email' => 'admin@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }
}
