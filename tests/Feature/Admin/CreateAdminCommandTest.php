<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
    }

    public function test_new_user_creation(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Name', 'Test Admin')
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Password', 'password123456')
            ->expectsQuestion('Confirm Password', 'password123456')
            ->expectsOutputToContain('Super Admin account created successfully')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'name' => 'Test Admin',
        ]);
    }

    public function test_password_hashing(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Name', 'Test Admin')
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Password', 'password123456')
            ->expectsQuestion('Confirm Password', 'password123456')
            ->assertExitCode(0);

        $user = User::where('email', 'admin@example.com')->first();
        $this->assertNotEquals('password123456', $user->password);
        $this->assertTrue(Hash::check('password123456', $user->password));
    }

    public function test_super_admin_role_assignment(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Name', 'Test Admin')
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Password', 'password123456')
            ->expectsQuestion('Confirm Password', 'password123456')
            ->assertExitCode(0);

        $user = User::where('email', 'admin@example.com')->first();
        $this->assertTrue($user->hasRole('Super Admin'));
    }

    public function test_duplicate_email_rejection(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('app:create-admin')
            ->expectsQuestion('Name', 'Test Admin')
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Password', 'password123456')
            ->expectsQuestion('Confirm Password', 'password123456')
            ->expectsOutputToContain('The email has already been taken')
            ->assertExitCode(1);
    }

    public function test_weak_password_rejection(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Name', 'Test Admin')
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Password', 'short')
            ->expectsQuestion('Confirm Password', 'short')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }

    public function test_password_confirmation_mismatch(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Name', 'Test Admin')
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Password', 'password123456')
            ->expectsQuestion('Confirm Password', 'differentpassword')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }
}
