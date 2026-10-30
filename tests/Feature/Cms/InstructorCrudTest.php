<?php

namespace Tests\Feature\Cms;

use App\Models\Instructor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_admin(): void
    {
        $this->get(route('admin.instructors.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_instructors_view_gets_403(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.instructors.index'))
            ->assertForbidden();
    }

    public function test_user_with_instructors_view_can_access_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.instructors.index'))
            ->assertOk();
    }

    public function test_index_displays_instructor_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $instructor = Instructor::factory()->create([
            'created_by' => $user->id,
            'name' => 'Coach Sarah',
        ]);

        $this->actingAs($user)->get(route('admin.instructors.index'))
            ->assertOk()
            ->assertSee('Coach Sarah');
    }

    public function test_user_with_instructors_create_can_access_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.instructors.create'))
            ->assertOk();
    }

    public function test_user_can_create_instructor_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'Coach Mike',
            'title' => 'Head Coach',
            'bio' => '<p>Experienced kickboxing coach.</p>',
            'short_bio' => 'Experienced coach',
        ])->assertRedirect();

        $this->assertDatabaseHas('instructors', [
            'name' => 'Coach Mike',
            'slug' => 'coach-mike',
        ]);
    }

    public function test_created_by_and_updated_by_set_correctly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'Ownership Test Instructor',
            'bio' => '<p>Test bio</p>',
        ]);

        $instructor = Instructor::where('slug', 'ownership-test-instructor')->first();
        $this->assertSame($user->id, $instructor->created_by);
        $this->assertSame($user->id, $instructor->updated_by);
    }

    public function test_slug_auto_generated_from_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'Coach Jessica Williams',
            'bio' => '<p>Bio content</p>',
        ]);

        $this->assertDatabaseHas('instructors', [
            'slug' => 'coach-jessica-williams',
        ]);
    }

    public function test_bio_sanitized_on_store(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'Sanitize Test Instructor',
            'bio' => '<p>Safe bio</p><script>alert("xss")</script>',
        ]);

        $instructor = Instructor::where('slug', 'sanitize-test-instructor')->first();
        $this->assertStringNotContainsString('<script>', $instructor->bio);
        $this->assertStringContainsString('<p>Safe bio</p>', $instructor->bio);
    }

    public function test_user_can_edit_instructor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $instructor = Instructor::factory()->create([
            'created_by' => $user->id,
            'name' => 'Original Name',
        ]);

        $this->actingAs($user)->put(route('admin.instructors.update', $instructor), [
            'name' => 'Updated Name',
            'bio' => '<p>Updated bio</p>',
        ])->assertRedirect();

        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_soft_delete_instructor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $instructor = Instructor::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->delete(route('admin.instructors.destroy', $instructor))
            ->assertRedirect(route('admin.instructors.index'));

        $this->assertSoftDeleted('instructors', ['id' => $instructor->id]);
    }

    public function test_user_can_restore_instructor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $instructor = Instructor::factory()->create([
            'created_by' => $user->id,
        ]);
        $instructor->delete();

        $this->actingAs($user)->post(route('admin.instructors.restore', $instructor))
            ->assertRedirect();

        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_force_delete_instructor(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $instructor = Instructor::factory()->create([
            'created_by' => $user->id,
        ]);
        $instructor->delete();

        $this->actingAs($user)->delete(route('admin.instructors.force-delete', $instructor))
            ->assertRedirect(route('admin.instructors.index'));

        $this->assertDatabaseMissing('instructors', ['id' => $instructor->id]);
    }

    public function test_portrait_media_upload_works(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->image('portrait.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'Media Upload Instructor',
            'bio' => '<p>Bio</p>',
            'featured_media' => $file,
            'alt_text' => 'Instructor portrait',
        ]);

        $instructor = Instructor::where('slug', 'media-upload-instructor')->first();
        $this->assertNotNull($instructor->getFirstMedia('portrait'));
    }

    public function test_media_validation_rejects_svg(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->create('image.svg', 100, 'image/svg+xml');

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'SVG Test Instructor',
            'bio' => '<p>Bio</p>',
            'featured_media' => $file,
        ])->assertSessionHasErrors('featured_media');
    }

    public function test_portrait_media_replacement_works(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $instructor = Instructor::factory()->create(['created_by' => $user->id]);
        $file1 = UploadedFile::fake()->image('portrait1.jpg', 800, 600);
        $instructor->addMedia($file1)->toMediaCollection('portrait');

        $file2 = UploadedFile::fake()->image('portrait2.jpg', 800, 600);
        $this->actingAs($user)->put(route('admin.instructors.update', $instructor), [
            'name' => $instructor->name,
            'bio' => $instructor->bio,
            'featured_media' => $file2,
            'alt_text' => 'New portrait',
        ]);

        $instructor->refresh();
        $media = $instructor->getFirstMedia('portrait');
        $this->assertNotNull($media);
        $this->assertSame('portrait2.jpg', $media->file_name);
    }

    public function test_alt_text_stored_as_custom_property(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->image('portrait.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'Alt Text Instructor',
            'bio' => '<p>Bio</p>',
            'featured_media' => $file,
            'alt_text' => 'My custom alt text',
        ]);

        $instructor = Instructor::where('slug', 'alt-text-instructor')->first();
        $media = $instructor->getFirstMedia('portrait');
        $this->assertNotNull($media);
        $this->assertSame('My custom alt text', $media->getCustomProperty('alt_text'));
    }

    public function test_remove_portrait_media_works(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $instructor = Instructor::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->image('portrait.jpg', 800, 600);
        $instructor->addMedia($file)->toMediaCollection('portrait');
        $this->assertNotNull($instructor->getFirstMedia('portrait'));

        $this->actingAs($user)->put(route('admin.instructors.update', $instructor), [
            'name' => $instructor->name,
            'bio' => $instructor->bio,
            'remove_featured_media' => true,
        ]);

        $instructor->refresh();
        $this->assertNull($instructor->getFirstMedia('portrait'));
    }

    public function test_active_flag_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'Inactive Instructor',
            'bio' => '<p>Bio</p>',
            'active' => false,
        ]);

        $instructor = Instructor::where('slug', 'inactive-instructor')->first();
        $this->assertFalse($instructor->active);
    }

    public function test_sort_order_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'Sorted Instructor',
            'bio' => '<p>Bio</p>',
            'sort_order' => 3,
        ]);

        $instructor = Instructor::where('slug', 'sorted-instructor')->first();
        $this->assertSame(3, $instructor->sort_order);
    }

    public function test_mass_assignment_protection_cannot_set_created_by(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $otherUser = User::factory()->create();

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'Mass Assignment Instructor',
            'bio' => '<p>Bio</p>',
            'created_by' => $otherUser->id,
            'updated_by' => $otherUser->id,
        ]);

        $instructor = Instructor::where('slug', 'mass-assignment-instructor')->first();
        $this->assertSame($user->id, $instructor->created_by);
        $this->assertSame($user->id, $instructor->updated_by);
    }

    public function test_duplicate_slug_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        Instructor::factory()->create([
            'name' => 'Existing Instructor',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('admin.instructors.store'), [
            'name' => 'Existing Instructor',
            'bio' => '<p>Bio</p>',
        ])->assertRedirect();

        $this->assertDatabaseCount('instructors', 2);

        $slugs = Instructor::where('name', 'Existing Instructor')->pluck('slug')->toArray();
        $this->assertCount(2, array_unique($slugs));
    }
}
