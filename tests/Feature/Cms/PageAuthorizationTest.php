<?php

namespace Tests\Feature\Cms;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageAuthorizationTest extends TestCase
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

        $page = Page::factory()->create(['created_by' => $superAdmin->id]);

        $this->actingAs($superAdmin)->get(route('admin.pages.index'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.pages.create'))->assertOk();
        $this->actingAs($superAdmin)->get(route('admin.pages.edit', $page))->assertOk();

        $this->actingAs($superAdmin)->post(route('admin.pages.store'), [
            'title' => 'Super Admin Page',
        ])->assertRedirect();

        $this->actingAs($superAdmin)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
        ])->assertRedirect();

        $this->actingAs($superAdmin)->delete(route('admin.pages.destroy', $page))
            ->assertRedirect(route('admin.pages.index'));
    }

    public function test_administrator_can_manage_pages(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $page = Page::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.pages.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.pages.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.pages.edit', $page))->assertOk();

        $this->actingAs($admin)->post(route('admin.pages.store'), [
            'title' => 'Admin Page',
        ])->assertRedirect();

        $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
        ])->assertRedirect();

        $this->actingAs($admin)->delete(route('admin.pages.destroy', $page))
            ->assertRedirect(route('admin.pages.index'));
    }

    public function test_editor_can_create_edit_but_not_delete(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $editor->id]);

        // Can access index
        $this->actingAs($editor)->get(route('admin.pages.index'))->assertOk();

        // Can create
        $this->actingAs($editor)->get(route('admin.pages.create'))->assertOk();
        $this->actingAs($editor)->post(route('admin.pages.store'), [
            'title' => 'Editor Page',
        ])->assertRedirect();

        // Can edit
        $this->actingAs($editor)->get(route('admin.pages.edit', $page))->assertOk();
        $this->actingAs($editor)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
        ])->assertRedirect();

        // Cannot delete
        $this->actingAs($editor)->delete(route('admin.pages.destroy', $page))
            ->assertForbidden();
    }

    public function test_editor_authored_page_cannot_be_deleted_by_editor(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $editor->id]);

        $this->actingAs($editor)->delete(route('admin.pages.destroy', $page))
            ->assertForbidden();

        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    }

    public function test_user_without_pages_update_cannot_update_authored_page(): void
    {
        $user = User::factory()->create();

        $page = Page::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->put(route('admin.pages.update', $page), [
            'title' => 'Hacked Title',
        ])->assertForbidden();
    }

    public function test_user_without_any_role_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.pages.index'))
            ->assertForbidden();

        $this->actingAs($user)->get(route('admin.pages.create'))
            ->assertForbidden();
    }

    public function test_publish_requires_pages_publish_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('pages.view');
        $user->givePermissionTo('pages.create');
        $user->givePermissionTo('pages.update');

        $page = Page::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->post(route('admin.pages.publish', $page))
            ->assertForbidden();

        $this->actingAs($user)->post(route('admin.pages.unpublish', $page))
            ->assertForbidden();
    }
}
