<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
    }

    public function test_user_model_supports_two_factor_authentication(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(method_exists($user, 'hasEnabledTwoFactorAuthentication'));
        $this->assertTrue(method_exists($user, 'twoFactorQrCodeSvg'));
    }

    public function test_authenticated_user_can_access_security_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.security'));

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_access_security_screen(): void
    {
        $response = $this->get(route('admin.security'));

        $response->assertRedirect(route('login'));
    }

    public function test_enabling_two_factor_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => 'password123456',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('two-factor.enable'));

        $response->assertRedirect();
    }

    public function test_two_factor_setup_generates_secret(): void
    {
        $user = User::factory()->create([
            'password' => 'password123456',
        ]);

        $this->actingAs($user);
        $this->withSession(['auth.password_confirmed_at' => time()]);

        $response = $this->post(route('two-factor.enable'));

        $user->refresh();
        $this->assertNotEmpty($user->two_factor_secret);
    }

    public function test_recovery_codes_are_generated(): void
    {
        $user = User::factory()->create([
            'password' => 'password123456',
        ]);

        $this->actingAs($user);
        $this->withSession(['auth.password_confirmed_at' => time()]);

        $response = $this->post(route('two-factor.enable'));

        $user->refresh();
        $this->assertNotEmpty($user->two_factor_recovery_codes);
    }

    public function test_two_factor_enabled_user_is_redirected_to_challenge(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $response->assertRedirect(route('two-factor.login'));
    }

    public function test_recovery_code_can_complete_authentication(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $recoveryCodes = ['recovery-code-1', 'recovery-code-2'];

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password123456',
        ]);

        $response = $this->post(route('two-factor.login.store'), [
            'recovery_code' => 'recovery-code-1',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_disabling_two_factor_removes_second_factor(): void
    {
        $user = User::factory()->create([
            'password' => 'password123456',
        ]);

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->actingAs($user);
        $this->withSession(['auth.password_confirmed_at' => time()]);

        $response = $this->delete(route('two-factor.disable'));

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
    }
}
