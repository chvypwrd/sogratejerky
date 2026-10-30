<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
    }

    public function test_forgot_password_form_displays(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertSee('Reset Password');
    }

    public function test_known_administrator_can_request_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'admin@example.com',
        ]);

        $response->assertSessionHas('status');
    }

    public function test_reset_notification_is_dispatched(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => 'admin@example.com',
        ]);

        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );
    }

    public function test_valid_token_can_reset_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $token = app('auth.password.broker')->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'admin@example.com',
            'password' => 'newpassword123456',
            'password_confirmation' => 'newpassword123456',
        ]);

        $response->assertSessionHas('status');
    }

    public function test_invalid_token_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'admin@example.com',
            'password' => 'newpassword123456',
            'password_confirmation' => 'newpassword123456',
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_reset_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $token = app('auth.password.broker')->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'admin@example.com',
            'password' => 'newpassword123456',
            'password_confirmation' => 'newpassword123456',
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123456', $user->password));
    }

    public function test_user_can_authenticate_using_new_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'oldpassword123456',
        ]);

        $token = app('auth.password.broker')->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'admin@example.com',
            'password' => 'newpassword123456',
            'password_confirmation' => 'newpassword123456',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'newpassword123456',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_old_password_no_longer_works(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'oldpassword123456',
        ]);

        $token = app('auth.password.broker')->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'admin@example.com',
            'password' => 'newpassword123456',
            'password_confirmation' => 'newpassword123456',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'oldpassword123456',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }
}
