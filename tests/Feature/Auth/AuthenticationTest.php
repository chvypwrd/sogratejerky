<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('Super Admin');

        return $user;
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
        $user = $this->admin([
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
        $user = $this->admin([
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
        $user = $this->admin([
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
        $user = $this->admin([
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
        $user = $this->admin([
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('login'));

        $response->assertRedirect();
    }

    public function test_customer_registration_route_exists(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
    }

    public function test_customer_logging_in_through_admin_form_goes_to_account(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password123456',
        ]);

        $this->post(route('login'), [
            'email' => 'customer@example.com',
            'password' => 'password123456',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_admin_intended_destination_is_preserved(): void
    {
        $this->admin([
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $this->withSession(['url.intended' => route('admin.orders.index')])
            ->post(route('login'), [
                'email' => 'admin@example.com',
                'password' => 'password123456',
            ])
            ->assertRedirect(route('admin.orders.index'));
    }

    public function test_customer_intended_admin_url_falls_back_to_account(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password123456',
        ]);

        $this->withSession(['url.intended' => route('admin.dashboard')])
            ->post(route('login'), [
                'email' => 'customer@example.com',
                'password' => 'password123456',
            ])
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_external_intended_url_is_not_followed(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'password123456',
        ]);

        $this->withSession(['url.intended' => 'https://evil.example/phishing'])
            ->post(route('login'), [
                'email' => 'customer@example.com',
                'password' => 'password123456',
            ])
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_repeated_failed_login_attempts_trigger_throttling(): void
    {
        $user = $this->admin([
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
