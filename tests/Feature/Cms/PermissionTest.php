<?php

namespace Tests\Feature\Cms;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_verify_54_permissions_total(): void
    {
        $this->assertEquals(54, Permission::count());
    }

    public function test_administrator_has_redirects_view_and_manage(): void
    {
        $admin = Role::where('name', 'Administrator')->first();

        $this->assertTrue($admin->hasPermissionTo('redirects.view'));
        $this->assertTrue($admin->hasPermissionTo('redirects.manage'));
    }

    public function test_editor_does_not_have_redirects_view_or_manage(): void
    {
        $editor = Role::where('name', 'Editor')->first();

        $this->assertFalse($editor->hasPermissionTo('redirects.view'));
        $this->assertFalse($editor->hasPermissionTo('redirects.manage'));
    }

    public function test_seeder_runs_twice_safely(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertEquals(54, Permission::count());
        $this->assertEquals(3, Role::count());
    }
}
