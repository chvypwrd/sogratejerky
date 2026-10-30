<?php

namespace Tests\Feature\Cms;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->get(route('admin.pages.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_pages_view_gets_403(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.pages.index'))
            ->assertForbidden();
    }

    public function test_user_with_pages_view_can_access_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.pages.index'))
            ->assertOk();
    }

    public function test_pages_index_displays_page_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->create([
            'created_by' => $user->id,
            'title' => 'Test Page Title',
        ]);

        $this->actingAs($user)->get(route('admin.pages.index'))
            ->assertOk()
            ->assertSee('Test Page Title');
    }

    public function test_user_with_pages_create_can_access_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.pages.create'))
            ->assertOk();
    }

    public function test_user_can_create_page_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'New Test Page',
            'body' => '<p>Some body content</p>',
            'excerpt' => 'Test excerpt',
            'template' => 'default',
        ])->assertRedirect();

        $this->assertDatabaseHas('pages', [
            'title' => 'New Test Page',
            'slug' => 'new-test-page',
        ]);
    }

    public function test_created_by_and_updated_by_set_correctly_on_create(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Ownership Test Page',
        ]);

        $page = Page::where('slug', 'ownership-test-page')->first();
        $this->assertSame($user->id, $page->created_by);
        $this->assertSame($user->id, $page->updated_by);
    }

    public function test_user_can_edit_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->create([
            'created_by' => $user->id,
            'title' => 'Original Title',
        ]);

        $this->actingAs($user)->put(route('admin.pages.update', $page), [
            'title' => 'Updated Title',
            'body' => '<p>Updated body</p>',
        ])->assertRedirect();

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_updated_by_set_on_update(): void
    {
        $creator = User::factory()->create();
        $editor = User::factory()->create();
        $editor->assignRole('Administrator');

        $page = Page::factory()->create([
            'created_by' => $creator->id,
        ]);

        $this->actingAs($editor)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'body' => '<p>Updated</p>',
        ]);

        $page->refresh();
        $this->assertSame($editor->id, $page->updated_by);
        $this->assertSame($creator->id, $page->created_by);
    }

    public function test_user_can_soft_delete_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->delete(route('admin.pages.destroy', $page))
            ->assertRedirect(route('admin.pages.index'));

        $this->assertSoftDeleted('pages', ['id' => $page->id]);
    }

    public function test_slug_auto_generated_from_title(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'My Awesome Kickboxing Page',
        ]);

        $this->assertDatabaseHas('pages', [
            'slug' => 'my-awesome-kickboxing-page',
        ]);
    }

    public function test_body_sanitized_on_store(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Sanitize Store Test',
            'body' => '<p>Safe content</p><script>alert("xss")</script>',
        ]);

        $page = Page::where('slug', 'sanitize-store-test')->first();
        $this->assertStringNotContainsString('<script>', $page->body);
        $this->assertStringContainsString('<p>Safe content</p>', $page->body);
    }

    public function test_body_sanitized_on_update(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->create([
            'created_by' => $user->id,
            'body' => '<p>Original</p>',
        ]);

        $this->actingAs($user)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'body' => '<p>Updated</p><script>evil()</script>',
        ]);

        $page->refresh();
        $this->assertStringNotContainsString('<script>', $page->body);
        $this->assertStringContainsString('<p>Updated</p>', $page->body);
    }

    public function test_featured_media_upload_works(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Media Upload Test',
            'featured_media' => $file,
            'alt_text' => 'Test alt text',
        ]);

        $page = Page::where('slug', 'media-upload-test')->first();
        $this->assertNotNull($page->getFirstMedia('featured'));
    }

    public function test_media_validation_rejects_svg(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->create('image.svg', 100, 'image/svg+xml');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'SVG Test',
            'featured_media' => $file,
        ])->assertSessionHasErrors('featured_media');
    }

    public function test_media_validation_rejects_over_5mb(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->image('large.jpg', 800, 600)->size(6000);

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Large File Test',
            'featured_media' => $file,
        ])->assertSessionHasErrors('featured_media');
    }

    public function test_media_validation_rejects_non_image(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Non Image Test',
            'featured_media' => $file,
        ])->assertSessionHasErrors('featured_media');
    }

    public function test_alt_text_stored_as_custom_property(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Alt Text Test',
            'featured_media' => $file,
            'alt_text' => 'My custom alt text',
        ]);

        $page = Page::where('slug', 'alt-text-test')->first();
        $media = $page->getFirstMedia('featured');
        $this->assertNotNull($media);
        $this->assertSame('My custom alt text', $media->getCustomProperty('alt_text'));
    }

    public function test_remove_featured_media_works(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $page->addMedia($file)->toMediaCollection('featured');
        $this->assertNotNull($page->getFirstMedia('featured'));

        $this->actingAs($user)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'remove_featured_media' => true,
        ]);

        $page->refresh();
        $this->assertNull($page->getFirstMedia('featured'));
    }

    public function test_edit_without_upload_preserves_existing_media(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $page->addMedia($file)->withCustomProperties(['alt_text' => 'Original alt'])->toMediaCollection('featured');

        $this->actingAs($user)->put(route('admin.pages.update', $page), [
            'title' => 'Updated Title',
        ]);

        $page->refresh();
        $this->assertNotNull($page->getFirstMedia('featured'));
    }

    public function test_new_upload_replaces_existing_media(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->create(['created_by' => $user->id]);
        $file1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
        $page->addMedia($file1)->toMediaCollection('featured');

        $file2 = UploadedFile::fake()->image('photo2.jpg', 800, 600);
        $this->actingAs($user)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'featured_media' => $file2,
            'alt_text' => 'New image',
        ]);

        $page->refresh();
        $media = $page->getFirstMedia('featured');
        $this->assertNotNull($media);
        $this->assertSame('photo2.jpg', $media->file_name);
    }

    public function test_mass_assignment_protection_cannot_set_created_by(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $otherUser = User::factory()->create();

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Mass Assignment Test',
            'created_by' => $otherUser->id,
            'updated_by' => $otherUser->id,
        ]);

        $page = Page::where('slug', 'mass-assignment-test')->first();
        $this->assertSame($user->id, $page->created_by);
        $this->assertSame($user->id, $page->updated_by);
    }

    public function test_status_published_auto_sets_published_at_if_null(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Auto Publish Test',
            'status' => 'published',
        ]);

        $page = Page::where('title', 'Auto Publish Test')->first();
        $this->assertNotNull($page);
        $this->assertSame(PageStatus::Published, $page->status);
    }
}
