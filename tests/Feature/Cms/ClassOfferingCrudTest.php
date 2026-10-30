<?php

namespace Tests\Feature\Cms;

use App\Models\ClassOffering;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClassOfferingCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_admin(): void
    {
        $this->get(route('admin.classes.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_classes_view_gets_403(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.classes.index'))
            ->assertForbidden();
    }

    public function test_user_with_classes_view_can_access_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.classes.index'))
            ->assertOk();
    }

    public function test_index_displays_class_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $class = ClassOffering::factory()->create([
            'created_by' => $user->id,
            'title' => 'Beginner Kickboxing',
        ]);

        $this->actingAs($user)->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('Beginner Kickboxing');
    }

    public function test_user_with_classes_create_can_access_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.classes.create'))
            ->assertOk();
    }

    public function test_user_can_create_class_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'Advanced Kickboxing',
            'description' => '<p>Advanced techniques and combinations.</p>',
            'short_description' => 'Advanced level class',
            'level' => 'Advanced',
            'duration' => '60 min',
        ])->assertRedirect();

        $this->assertDatabaseHas('class_offerings', [
            'title' => 'Advanced Kickboxing',
            'slug' => 'advanced-kickboxing',
        ]);
    }

    public function test_created_by_and_updated_by_set_correctly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'Ownership Test Class',
            'description' => '<p>Test content</p>',
        ]);

        $class = ClassOffering::where('slug', 'ownership-test-class')->first();
        $this->assertSame($user->id, $class->created_by);
        $this->assertSame($user->id, $class->updated_by);
    }

    public function test_slug_auto_generated_from_title(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'My Awesome Kickboxing Class',
            'description' => '<p>Content here</p>',
        ]);

        $this->assertDatabaseHas('class_offerings', [
            'slug' => 'my-awesome-kickboxing-class',
        ]);
    }

    public function test_description_sanitized_on_store(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'Sanitize Test Class',
            'description' => '<p>Safe content</p><script>alert("xss")</script>',
        ]);

        $class = ClassOffering::where('slug', 'sanitize-test-class')->first();
        $this->assertStringNotContainsString('<script>', $class->description);
        $this->assertStringContainsString('<p>Safe content</p>', $class->description);
    }

    public function test_user_can_edit_class(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $class = ClassOffering::factory()->create([
            'created_by' => $user->id,
            'title' => 'Original Title',
        ]);

        $this->actingAs($user)->put(route('admin.classes.update', $class), [
            'title' => 'Updated Title',
            'description' => '<p>Updated content</p>',
        ])->assertRedirect();

        $this->assertDatabaseHas('class_offerings', [
            'id' => $class->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_user_can_soft_delete_class(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $class = ClassOffering::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->delete(route('admin.classes.destroy', $class))
            ->assertRedirect(route('admin.classes.index'));

        $this->assertSoftDeleted('class_offerings', ['id' => $class->id]);
    }

    public function test_user_can_restore_class(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $class = ClassOffering::factory()->create([
            'created_by' => $user->id,
        ]);
        $class->delete();

        $this->actingAs($user)->post(route('admin.classes.restore', $class))
            ->assertRedirect();

        $this->assertDatabaseHas('class_offerings', [
            'id' => $class->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_force_delete_class(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $class = ClassOffering::factory()->create([
            'created_by' => $user->id,
        ]);
        $class->delete();

        $this->actingAs($user)->delete(route('admin.classes.force-delete', $class))
            ->assertRedirect(route('admin.classes.index'));

        $this->assertDatabaseMissing('class_offerings', ['id' => $class->id]);
    }

    public function test_media_upload_works(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'Media Upload Class',
            'description' => '<p>Content</p>',
            'featured_media' => $file,
            'alt_text' => 'Class photo',
        ]);

        $class = ClassOffering::where('slug', 'media-upload-class')->first();
        $this->assertNotNull($class->getFirstMedia('featured'));
    }

    public function test_media_validation_rejects_svg(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->create('image.svg', 100, 'image/svg+xml');

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'SVG Test Class',
            'description' => '<p>Content</p>',
            'featured_media' => $file,
        ])->assertSessionHasErrors('featured_media');
    }

    public function test_media_replacement_works(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $class = ClassOffering::factory()->create(['created_by' => $user->id]);
        $file1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
        $class->addMedia($file1)->toMediaCollection('featured');

        $file2 = UploadedFile::fake()->image('photo2.jpg', 800, 600);
        $this->actingAs($user)->put(route('admin.classes.update', $class), [
            'title' => $class->title,
            'description' => $class->description,
            'featured_media' => $file2,
            'alt_text' => 'New image',
        ]);

        $class->refresh();
        $media = $class->getFirstMedia('featured');
        $this->assertNotNull($media);
        $this->assertSame('photo2.jpg', $media->file_name);
    }

    public function test_alt_text_stored_as_custom_property(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'Alt Text Class',
            'description' => '<p>Content</p>',
            'featured_media' => $file,
            'alt_text' => 'My custom alt text',
        ]);

        $class = ClassOffering::where('slug', 'alt-text-class')->first();
        $media = $class->getFirstMedia('featured');
        $this->assertNotNull($media);
        $this->assertSame('My custom alt text', $media->getCustomProperty('alt_text'));
    }

    public function test_remove_featured_media_works(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $class = ClassOffering::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $class->addMedia($file)->toMediaCollection('featured');
        $this->assertNotNull($class->getFirstMedia('featured'));

        $this->actingAs($user)->put(route('admin.classes.update', $class), [
            'title' => $class->title,
            'description' => $class->description,
            'remove_featured_media' => true,
        ]);

        $class->refresh();
        $this->assertNull($class->getFirstMedia('featured'));
    }

    public function test_featured_flag_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'Featured Class',
            'description' => '<p>Content</p>',
            'featured' => true,
        ]);

        $class = ClassOffering::where('slug', 'featured-class')->first();
        $this->assertTrue($class->featured);
    }

    public function test_active_flag_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'Inactive Class',
            'description' => '<p>Content</p>',
            'active' => false,
        ]);

        $class = ClassOffering::where('slug', 'inactive-class')->first();
        $this->assertFalse($class->active);
    }

    public function test_sort_order_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'Sorted Class',
            'description' => '<p>Content</p>',
            'sort_order' => 5,
        ]);

        $class = ClassOffering::where('slug', 'sorted-class')->first();
        $this->assertSame(5, $class->sort_order);
    }

    public function test_mass_assignment_protection_cannot_set_created_by(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $otherUser = User::factory()->create();

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'Mass Assignment Class',
            'description' => '<p>Content</p>',
            'created_by' => $otherUser->id,
            'updated_by' => $otherUser->id,
        ]);

        $class = ClassOffering::where('slug', 'mass-assignment-class')->first();
        $this->assertSame($user->id, $class->created_by);
        $this->assertSame($user->id, $class->updated_by);
    }

    public function test_duplicate_slug_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        ClassOffering::factory()->create([
            'title' => 'Existing Class',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('admin.classes.store'), [
            'title' => 'Existing Class',
            'description' => '<p>Content</p>',
        ])->assertRedirect();

        $this->assertDatabaseCount('class_offerings', 2);

        $slugs = ClassOffering::where('title', 'Existing Class')->pluck('slug')->toArray();
        $this->assertCount(2, array_unique($slugs));
    }
}
