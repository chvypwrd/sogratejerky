<?php

namespace Tests\Feature\Cms;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageTrashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        Route::bind('page', fn (string $value) => Page::withTrashed()->where('slug', $value)->firstOrFail());
    }

    public function test_trashed_page_returns_404_publicly(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk();

        $page->delete();

        $this->get(route('pages.show', $page->slug))
            ->assertNotFound();
    }

    public function test_restore_brings_page_back(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->create(['created_by' => $user->id]);
        $page->delete();
        $this->assertSoftDeleted('pages', ['id' => $page->id]);

        $this->actingAs($user)->post(route('admin.pages.restore', $page))
            ->assertRedirect();

        $this->assertNotSoftDeleted('pages', ['id' => $page->id]);
    }

    public function test_force_delete_permanently_removes_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->create(['created_by' => $user->id]);
        $pageId = $page->id;
        $page->delete();

        $this->actingAs($user)->delete(route('admin.pages.force-delete', $page))
            ->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseMissing('pages', ['id' => $pageId]);
    }

    public function test_force_delete_cleans_media(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $page->addMedia($file)->toMediaCollection('featured');
        $this->assertNotNull($page->getFirstMedia('featured'));

        $mediaId = $page->getFirstMedia('featured')->id;
        $page->delete();

        $this->actingAs($user)->delete(route('admin.pages.force-delete', $page));

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        $this->assertDatabaseMissing('media', ['id' => $mediaId]);
    }

    public function test_only_users_with_pages_delete_can_trash(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $editor->id]);

        $this->actingAs($editor)->delete(route('admin.pages.destroy', $page))
            ->assertForbidden();
    }

    public function test_only_users_with_pages_delete_can_restore(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $editor->id]);
        $page->delete();

        $this->actingAs($editor)->post(route('admin.pages.restore', $page))
            ->assertForbidden();
    }

    public function test_only_users_with_pages_delete_can_force_delete(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $editor->id]);
        $page->delete();

        $this->actingAs($editor)->delete(route('admin.pages.force-delete', $page))
            ->assertForbidden();
    }

    public function test_editor_cannot_trash_pages(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $editor->id]);

        $this->actingAs($editor)->delete(route('admin.pages.destroy', $page))
            ->assertForbidden();
    }

    public function test_administrator_can_trash_restore_force_delete(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');

        $page = Page::factory()->create(['created_by' => $admin->id]);

        // Trash
        $this->actingAs($admin)->delete(route('admin.pages.destroy', $page))
            ->assertRedirect(route('admin.pages.index'));
        $this->assertSoftDeleted('pages', ['id' => $page->id]);

        // Restore
        $this->actingAs($admin)->post(route('admin.pages.restore', $page))
            ->assertRedirect();
        $this->assertNotSoftDeleted('pages', ['id' => $page->id]);

        // Trash again then force delete
        $page->delete();
        $this->actingAs($admin)->delete(route('admin.pages.force-delete', $page))
            ->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }
}
