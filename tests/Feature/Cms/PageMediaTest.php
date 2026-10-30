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

class PageMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        Route::bind('page', fn (string $value) => Page::withTrashed()->where('slug', $value)->firstOrFail());
    }

    public function test_jpeg_accepted(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'JPEG Test',
            'featured_media' => $file,
        ]);

        $page = Page::where('slug', 'jpeg-test')->first();
        $this->assertNotNull($page->getFirstMedia('featured'));
    }

    public function test_png_accepted(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('photo.png', 800, 600)->mimeType('image/png');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'PNG Test',
            'featured_media' => $file,
        ]);

        $page = Page::where('slug', 'png-test')->first();
        $this->assertNotNull($page->getFirstMedia('featured'));
    }

    public function test_webp_accepted(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('photo.webp', 800, 600)->mimeType('image/webp');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'WebP Test',
            'featured_media' => $file,
        ]);

        $page = Page::where('slug', 'webp-test')->first();
        $this->assertNotNull($page->getFirstMedia('featured'));
    }

    public function test_svg_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->create('image.svg', 100, 'image/svg+xml');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'SVG Test',
            'featured_media' => $file,
        ])->assertSessionHasErrors('featured_media');
    }

    public function test_gif_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->create('animation.gif', 100, 'image/gif');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'GIF Test',
            'featured_media' => $file,
        ])->assertSessionHasErrors('featured_media');
    }

    public function test_non_image_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'PDF Test',
            'featured_media' => $file,
        ])->assertSessionHasErrors('featured_media');
    }

    public function test_over_5mb_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('large.jpg', 800, 600)->size(6000);

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Large Test',
            'featured_media' => $file,
        ])->assertSessionHasErrors('featured_media');
    }

    public function test_excessive_dimensions_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('wide.jpg', 9000, 100);

        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Wide Test',
            'featured_media' => $file,
        ])->assertSessionHasErrors('featured_media');
    }

    public function test_single_file_replacement_edit_without_upload_preserves(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();

        $page = Page::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->image('original.jpg', 800, 600);
        $page->addMedia($file)->withCustomProperties(['alt_text' => 'Original'])->toMediaCollection('featured');

        $this->actingAs($user)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
        ]);

        $page->refresh();
        $media = $page->getFirstMedia('featured');
        $this->assertNotNull($media);
        $this->assertSame('original.jpg', $media->file_name);
    }

    public function test_single_file_replacement_new_upload_replaces(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();

        $page = Page::factory()->create(['created_by' => $user->id]);
        $file1 = UploadedFile::fake()->image('old.jpg', 800, 600);
        $page->addMedia($file1)->toMediaCollection('featured');

        $file2 = UploadedFile::fake()->image('new.jpg', 800, 600);
        $this->actingAs($user)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'featured_media' => $file2,
        ]);

        $page->refresh();
        $media = $page->getFirstMedia('featured');
        $this->assertNotNull($media);
        $this->assertSame('new.jpg', $media->file_name);
    }

    public function test_explicit_removal(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();

        $page = Page::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $page->addMedia($file)->toMediaCollection('featured');

        $this->actingAs($user)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'remove_featured_media' => true,
        ]);

        $page->refresh();
        $this->assertNull($page->getFirstMedia('featured'));
    }

    public function test_alt_text_persists(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $this->actingAs($user)->post(route('admin.pages.store'), [
            'title' => 'Alt Text Persistence',
            'featured_media' => $file,
            'alt_text' => 'Persistent alt text',
        ]);

        $page = Page::where('slug', 'alt-text-persistence')->first();
        $media = $page->getFirstMedia('featured');
        $this->assertSame('Persistent alt text', $media->getCustomProperty('alt_text'));

        $this->actingAs($user)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'alt_text' => 'Updated alt text',
        ]);

        $page->refresh();
        $media = $page->getFirstMedia('featured');
        $this->assertSame('Updated alt text', $media->getCustomProperty('alt_text'));
    }

    public function test_unauthorized_media_modification_rejected(): void
    {
        Storage::fake('local');
        $admin = $this->createAdminUser();

        $page = Page::factory()->create(['created_by' => $admin->id]);
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $page->addMedia($file)->toMediaCollection('featured');

        $unauthorized = User::factory()->create();

        $newFile = UploadedFile::fake()->image('hacked.jpg', 800, 600);
        $this->actingAs($unauthorized)->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'featured_media' => $newFile,
        ])->assertForbidden();
    }

    public function test_force_delete_cleanup(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();

        $page = Page::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $page->addMedia($file)->toMediaCollection('featured');

        $mediaId = $page->getFirstMedia('featured')->id;
        $page->delete();

        $this->actingAs($user)->delete(route('admin.pages.force-delete', $page));

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        $this->assertDatabaseMissing('media', ['id' => $mediaId]);
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }
}
