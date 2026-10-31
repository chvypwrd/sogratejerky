<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'owner@sograte.test';

    private const PASSWORD = 'SOGRATE-Admin-2026!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->setCredentials();
    }

    protected function tearDown(): void
    {
        Env::getRepository()->clear('ADMIN_SEED_NAME');
        Env::getRepository()->clear('ADMIN_SEED_EMAIL');
        Env::getRepository()->clear('ADMIN_SEED_PASSWORD');
        parent::tearDown();
    }

    private function setCredentials(string $email = self::EMAIL, string $password = self::PASSWORD, ?string $name = 'SOGRATE Owner'): void
    {
        $repository = Env::getRepository();
        $repository->set('ADMIN_SEED_EMAIL', $email);
        $repository->set('ADMIN_SEED_PASSWORD', $password);

        if ($name === null) {
            $repository->clear('ADMIN_SEED_NAME');
        } else {
            $repository->set('ADMIN_SEED_NAME', $name);
        }
    }

    public function test_seeder_creates_one_verified_super_admin(): void
    {
        $this->seed(AdminUserSeeder::class);

        $user = User::where('email', self::EMAIL)->firstOrFail();

        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('Super Admin'));
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertSame(1, User::where('email', self::EMAIL)->count());
    }

    public function test_seeder_is_idempotent_and_preserves_password(): void
    {
        $this->seed(AdminUserSeeder::class);
        $originalHash = User::where('email', self::EMAIL)->firstOrFail()->password;

        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', self::EMAIL)->count());
        $this->assertSame($originalHash, User::where('email', self::EMAIL)->firstOrFail()->password);
        $this->assertTrue(User::where('email', self::EMAIL)->firstOrFail()->hasRole('Super Admin'));
    }

    public function test_existing_administrator_password_is_not_overwritten(): void
    {
        $user = User::factory()->create([
            'email' => self::EMAIL,
            'password' => 'existing-password',
        ]);
        $user->assignRole('Super Admin');
        $originalHash = $user->fresh()->password;

        $this->seed(AdminUserSeeder::class);

        $this->assertSame($originalHash, $user->fresh()->password);
        $this->assertTrue(Hash::check('existing-password', $user->fresh()->password));
    }

    public function test_existing_customer_is_not_promoted(): void
    {
        $customer = User::factory()->create(['email' => self::EMAIL]);
        $originalHash = $customer->password;

        try {
            $this->seed(AdminUserSeeder::class);
            $this->fail('Seeder promoted an existing customer.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Refusing to promote an existing customer', $e->getMessage());
        }

        $this->assertFalse($customer->fresh()->hasRole('Super Admin'));
        $this->assertSame($originalHash, $customer->fresh()->password);
    }

    public function test_missing_credentials_fail_safely(): void
    {
        Env::getRepository()->clear('ADMIN_SEED_EMAIL');
        Env::getRepository()->clear('ADMIN_SEED_PASSWORD');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Admin seeding requires');

        $this->seed(AdminUserSeeder::class);
    }

    public function test_weak_password_fails_safely(): void
    {
        $this->setCredentials(password: 'short');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Admin seeding requires');

        $this->seed(AdminUserSeeder::class);
    }

    public function test_missing_super_admin_role_fails_safely(): void
    {
        Role::where('name', 'Super Admin')->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Super Admin role is missing');

        $this->seed(AdminUserSeeder::class);
    }

    public function test_existing_administrator_keeps_two_factor_trait(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->assertTrue(method_exists(User::where('email', self::EMAIL)->firstOrFail(), 'hasEnabledTwoFactorAuthentication'));
    }
}
