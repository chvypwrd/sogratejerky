<?php

namespace Tests\Feature\Gallery;

use App\Models\Gallery;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_access_all_gallery_routes(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $gallery = Gallery::factory()->create(['created_by' => $superAdmin->id]);

        $this->actingAs($superAdmin)->get(route('admin.galleries.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.galleries.create'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.galleries.edit', $gallery))->assertOk();

        $this->actingAs($superAdmin)->post(route('admin.galleries.store'), [
            'title' => 'Super Admin Gallery',
            'description' => 'Created by super admin.',
        ])->assertRedirect();

        $this->actingAs($superAdmin)->put(route('admin.galleries.update', $gallery), [
            'title' => $gallery->title,
            'description' => $gallery->description,
        ])->assertRedirect();

        $this->actingAs($superAdmin)->delete(route('admin.galleries.destroy', $gallery))
            ->assertRedirect(route('admin.galleries.index'));
    }

    public function test_administrator_can_manage_galleries(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $gallery = Gallery::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.galleries.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.galleries.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.galleries.edit', $gallery))->assertOk();

        $this->actingAs($admin)->post(route('admin.galleries.store'), [
            'title' => 'Admin Gallery',
            'description' => 'Created by admin.',
        ])->assertRedirect();

        $this->actingAs($admin)->put(route('admin.galleries.update', $gallery), [
            'title' => $gallery->title,
            'description' => $gallery->description,
        ])->assertRedirect();

        $this->actingAs($admin)->delete(route('admin.galleries.destroy', $gallery))
            ->assertRedirect(route('admin.galleries.index'));
    }

    public function test_editor_can_create_edit_but_not_delete(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $gallery = Gallery::factory()->create(['created_by' => $editor->id]);

        $this->actingAs($editor)->get(route('admin.galleries.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.galleries.create'))->assertOk();

        $this->actingAs($editor)->post(route('admin.galleries.store'), [
            'title' => 'Editor Gallery',
            'description' => 'Created by editor.',
        ])->assertRedirect();

        $this->actingAs($editor)->get(route('admin.galleries.edit', $gallery))->assertOk();
        $this->actingAs($editor)->put(route('admin.galleries.update', $gallery), [
            'title' => $gallery->title,
            'description' => $gallery->description,
        ])->assertRedirect();

        $this->actingAs($editor)->delete(route('admin.galleries.destroy', $gallery))
            ->assertForbidden();
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.galleries.index'))
            ->assertForbidden();

        $this->actingAs($user)->get(route('admin.galleries.create'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_redirected(): void
    {
        $gallery = Gallery::factory()->create();

        $this->get(route('admin.galleries.index'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.galleries.create'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.galleries.edit', $gallery))
            ->assertRedirect(route('login'));
    }
}
