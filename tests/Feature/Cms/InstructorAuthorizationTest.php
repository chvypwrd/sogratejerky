<?php

namespace Tests\Feature\Cms;

use App\Models\Instructor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_access_all_routes(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $instructor = Instructor::factory()->create(['created_by' => $superAdmin->id]);

        $this->actingAs($superAdmin)->get(route('admin.instructors.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.instructors.create'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.instructors.edit', $instructor))->assertOk();

        $this->actingAs($superAdmin)->post(route('admin.instructors.store'), [
            'name' => 'Super Admin Instructor',
            'bio' => '<p>Bio</p>',
        ])->assertRedirect();

        $this->actingAs($superAdmin)->put(route('admin.instructors.update', $instructor), [
            'name' => $instructor->name,
            'bio' => $instructor->bio,
        ])->assertRedirect();

        $this->actingAs($superAdmin)->delete(route('admin.instructors.destroy', $instructor))
            ->assertRedirect(route('admin.instructors.index'));
    }

    public function test_administrator_can_manage_instructors(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $instructor = Instructor::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.instructors.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.instructors.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.instructors.edit', $instructor))->assertOk();

        $this->actingAs($admin)->post(route('admin.instructors.store'), [
            'name' => 'Admin Instructor',
            'bio' => '<p>Bio</p>',
        ])->assertRedirect();

        $this->actingAs($admin)->put(route('admin.instructors.update', $instructor), [
            'name' => $instructor->name,
            'bio' => $instructor->bio,
        ])->assertRedirect();

        $this->actingAs($admin)->delete(route('admin.instructors.destroy', $instructor))
            ->assertRedirect(route('admin.instructors.index'));
    }

    public function test_editor_can_create_edit_but_not_delete(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $instructor = Instructor::factory()->create(['created_by' => $editor->id]);

        $this->actingAs($editor)->get(route('admin.instructors.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.instructors.create'))->assertOk();

        $this->actingAs($editor)->post(route('admin.instructors.store'), [
            'name' => 'Editor Instructor',
            'bio' => '<p>Bio</p>',
        ])->assertRedirect();

        $this->actingAs($editor)->get(route('admin.instructors.edit', $instructor))->assertOk();
        $this->actingAs($editor)->put(route('admin.instructors.update', $instructor), [
            'name' => $instructor->name,
            'bio' => $instructor->bio,
        ])->assertRedirect();

        $this->actingAs($editor)->delete(route('admin.instructors.destroy', $instructor))
            ->assertForbidden();
    }

    public function test_user_without_instructors_delete_cannot_trash(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $instructor = Instructor::factory()->create(['created_by' => $editor->id]);

        $this->actingAs($editor)->delete(route('admin.instructors.destroy', $instructor))
            ->assertForbidden();

        $this->assertDatabaseHas('instructors', ['id' => $instructor->id]);
    }

    public function test_user_without_instructors_view_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.instructors.index'))
            ->assertForbidden();

        $this->actingAs($user)->get(route('admin.instructors.create'))
            ->assertForbidden();
    }
}
