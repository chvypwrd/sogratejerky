<?php

namespace Tests\Feature\Cms;

use App\Models\ClassOffering;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassOfferingAuthorizationTest extends TestCase
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

        $class = ClassOffering::factory()->create(['created_by' => $superAdmin->id]);

        $this->actingAs($superAdmin)->get(route('admin.classes.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.classes.create'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.classes.edit', $class))->assertOk();

        $this->actingAs($superAdmin)->post(route('admin.classes.store'), [
            'title' => 'Super Admin Class',
            'description' => '<p>Content</p>',
        ])->assertRedirect();

        $this->actingAs($superAdmin)->put(route('admin.classes.update', $class), [
            'title' => $class->title,
            'description' => $class->description,
        ])->assertRedirect();

        $this->actingAs($superAdmin)->delete(route('admin.classes.destroy', $class))
            ->assertRedirect(route('admin.classes.index'));
    }

    public function test_administrator_can_manage_classes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $class = ClassOffering::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.classes.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.classes.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.classes.edit', $class))->assertOk();

        $this->actingAs($admin)->post(route('admin.classes.store'), [
            'title' => 'Admin Class',
            'description' => '<p>Content</p>',
        ])->assertRedirect();

        $this->actingAs($admin)->put(route('admin.classes.update', $class), [
            'title' => $class->title,
            'description' => $class->description,
        ])->assertRedirect();

        $this->actingAs($admin)->delete(route('admin.classes.destroy', $class))
            ->assertRedirect(route('admin.classes.index'));
    }

    public function test_editor_can_create_edit_but_not_delete(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $class = ClassOffering::factory()->create(['created_by' => $editor->id]);

        $this->actingAs($editor)->get(route('admin.classes.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.classes.create'))->assertOk();

        $this->actingAs($editor)->post(route('admin.classes.store'), [
            'title' => 'Editor Class',
            'description' => '<p>Content</p>',
        ])->assertRedirect();

        $this->actingAs($editor)->get(route('admin.classes.edit', $class))->assertOk();
        $this->actingAs($editor)->put(route('admin.classes.update', $class), [
            'title' => $class->title,
            'description' => $class->description,
        ])->assertRedirect();

        $this->actingAs($editor)->delete(route('admin.classes.destroy', $class))
            ->assertForbidden();
    }

    public function test_user_without_classes_delete_cannot_trash(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $class = ClassOffering::factory()->create(['created_by' => $editor->id]);

        $this->actingAs($editor)->delete(route('admin.classes.destroy', $class))
            ->assertForbidden();

        $this->assertDatabaseHas('class_offerings', ['id' => $class->id]);
    }

    public function test_user_without_classes_view_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.classes.index'))
            ->assertForbidden();

        $this->actingAs($user)->get(route('admin.classes.create'))
            ->assertForbidden();
    }
}
