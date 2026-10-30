<?php

namespace Tests\Feature\Gallery;

use App\Models\Gallery;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_admin_galleries(): void
    {
        $this->get(route('admin.galleries.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_galleries_view_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.galleries.index'))
            ->assertForbidden();
    }

    public function test_user_with_galleries_view_can_access_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.galleries.index'))
            ->assertOk();
    }

    public function test_index_displays_gallery_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        Gallery::factory()->create([
            'title' => 'Summer Training 2026',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('admin.galleries.index'))
            ->assertOk()
            ->assertSee('Summer Training 2026');
    }

    public function test_user_with_galleries_create_can_access_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.galleries.create'))
            ->assertOk();
    }

    public function test_user_can_create_gallery_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Competition Photos',
            'description' => 'Photos from the 2026 regional competition.',
            'active' => true,
            'featured' => false,
            'sort_order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('galleries', [
            'title' => 'Competition Photos',
            'slug' => 'competition-photos',
        ]);
    }

    public function test_created_by_and_updated_by_set_correctly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Ownership Test Gallery',
            'description' => 'Testing ownership fields.',
        ]);

        $gallery = Gallery::where('slug', 'ownership-test-gallery')->first();
        $this->assertSame($user->id, $gallery->created_by);
        $this->assertSame($user->id, $gallery->updated_by);
    }

    public function test_user_can_edit_gallery(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $gallery = Gallery::factory()->create([
            'title' => 'Original Title',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->put(route('admin.galleries.update', $gallery), [
            'title' => 'Updated Title',
            'description' => 'Updated description.',
        ])->assertRedirect();

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_user_can_soft_delete_gallery(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $gallery = Gallery::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->delete(route('admin.galleries.destroy', $gallery))
            ->assertRedirect(route('admin.galleries.index'));

        $this->assertSoftDeleted('galleries', ['id' => $gallery->id]);
    }

    public function test_user_can_restore_gallery(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $gallery = Gallery::factory()->create([
            'created_by' => $user->id,
        ]);
        $gallery->delete();

        $this->actingAs($user)->post(route('admin.galleries.restore', $gallery))
            ->assertRedirect();

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_force_delete_gallery(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $gallery = Gallery::factory()->create([
            'created_by' => $user->id,
        ]);
        $gallery->delete();

        $this->actingAs($user)->delete(route('admin.galleries.force-delete', $gallery))
            ->assertRedirect(route('admin.galleries.index'));

        $this->assertDatabaseMissing('galleries', ['id' => $gallery->id]);
    }

    public function test_slug_auto_generated_from_title(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'My Awesome Gallery',
            'description' => 'Test description.',
        ]);

        $this->assertDatabaseHas('galleries', [
            'slug' => 'my-awesome-gallery',
        ]);
    }

    public function test_duplicate_slug_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        Gallery::factory()->create([
            'title' => 'Existing Gallery',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Existing Gallery',
            'description' => 'Duplicate.',
        ])->assertRedirect();

        $this->assertDatabaseCount('galleries', 2);

        $slugs = Gallery::where('title', 'Existing Gallery')->pluck('slug')->toArray();
        $this->assertCount(2, array_unique($slugs));
    }

    public function test_mass_assignment_protection_cannot_set_created_by(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $otherUser = User::factory()->create();

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Mass Assignment Gallery',
            'description' => 'Testing mass assignment protection.',
            'created_by' => $otherUser->id,
            'updated_by' => $otherUser->id,
        ]);

        $gallery = Gallery::where('slug', 'mass-assignment-gallery')->first();
        $this->assertSame($user->id, $gallery->created_by);
        $this->assertSame($user->id, $gallery->updated_by);
    }

    public function test_inactive_gallery_works_in_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        Gallery::factory()->inactive()->create([
            'title' => 'Inactive Gallery',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('admin.galleries.index'))
            ->assertOk()
            ->assertSee('Inactive Gallery');

        $this->actingAs($user)->get(route('admin.galleries.index', ['filter' => 'inactive']))
            ->assertOk()
            ->assertSee('Inactive Gallery');
    }
}
