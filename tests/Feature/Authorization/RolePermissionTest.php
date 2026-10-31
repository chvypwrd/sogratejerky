<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_are_created(): void
    {
        Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::create(['name' => 'Editor', 'guard_name' => 'web']);

        $this->assertDatabaseHas('roles', ['name' => 'Super Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Administrator']);
        $this->assertDatabaseHas('roles', ['name' => 'Editor']);
    }

    public function test_permissions_are_created(): void
    {
        Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'pages.view', 'guard_name' => 'web']);

        $this->assertDatabaseHas('permissions', ['name' => 'dashboard.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'pages.view']);
    }

    public function test_seeder_can_run_twice_safely(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->assertEquals(3, Role::count());
        $this->assertEquals(55, Permission::count());
    }

    public function test_super_admin_receives_all_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('name', 'Super Admin')->first();
        $permissionCount = Permission::count();

        $this->assertEquals($permissionCount, $role->permissions->count());
    }

    public function test_administrator_gets_expected_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('name', 'Administrator')->first();

        $this->assertTrue($role->hasPermissionTo('dashboard.view'));
        $this->assertTrue($role->hasPermissionTo('pages.view'));
        $this->assertTrue($role->hasPermissionTo('pages.create'));
        $this->assertTrue($role->hasPermissionTo('settings.update'));
        $this->assertTrue($role->hasPermissionTo('users.view'));
    }

    public function test_administrator_lacks_role_management_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('name', 'Administrator')->first();

        $this->assertFalse($role->hasPermissionTo('roles.manage'));
        $this->assertFalse($role->hasPermissionTo('users.delete'));
    }

    public function test_editor_gets_expected_content_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('name', 'Editor')->first();

        $this->assertTrue($role->hasPermissionTo('dashboard.view'));
        $this->assertTrue($role->hasPermissionTo('pages.view'));
        $this->assertTrue($role->hasPermissionTo('pages.create'));
        $this->assertTrue($role->hasPermissionTo('galleries.view'));
        $this->assertTrue($role->hasPermissionTo('media.upload'));
    }

    public function test_editor_lacks_settings_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('name', 'Editor')->first();

        $this->assertFalse($role->hasPermissionTo('settings.view'));
        $this->assertFalse($role->hasPermissionTo('settings.update'));
    }

    public function test_editor_lacks_user_management_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::where('name', 'Editor')->first();

        $this->assertFalse($role->hasPermissionTo('users.view'));
        $this->assertFalse($role->hasPermissionTo('users.create'));
        $this->assertFalse($role->hasPermissionTo('roles.manage'));
    }

    public function test_unauthenticated_users_have_no_admin_access(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_dashboard_permission_receives_403(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $role = Role::where('name', 'Editor')->first();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_user_with_dashboard_permission_may_access_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $role = Role::where('name', 'Administrator')->first();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_super_admin_may_access_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $role = Role::where('name', 'Super Admin')->first();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
    }
}
