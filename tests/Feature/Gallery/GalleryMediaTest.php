<?php

namespace Tests\Feature\Gallery;

use App\Models\Gallery;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    public function test_jpeg_accepted(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'JPEG Gallery',
            'photos' => [$file],
        ]);

        $gallery = Gallery::where('slug', 'jpeg-gallery')->first();
        $this->assertNotNull($gallery->getFirstMedia('photos'));
    }

    public function test_png_accepted(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('photo.png', 800, 600)->mimeType('image/png');

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'PNG Gallery',
            'photos' => [$file],
        ]);

        $gallery = Gallery::where('slug', 'png-gallery')->first();
        $this->assertNotNull($gallery->getFirstMedia('photos'));
    }

    public function test_webp_accepted(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('photo.webp', 800, 600)->mimeType('image/webp');

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'WebP Gallery',
            'photos' => [$file],
        ]);

        $gallery = Gallery::where('slug', 'webp-gallery')->first();
        $this->assertNotNull($gallery->getFirstMedia('photos'));
    }

    public function test_svg_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->create('image.svg', 100, 'image/svg+xml');

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'SVG Gallery',
            'photos' => [$file],
        ])->assertSessionHasErrors('photos.0');
    }

    public function test_gif_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->create('animation.gif', 100, 'image/gif');

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'GIF Gallery',
            'photos' => [$file],
        ])->assertSessionHasErrors('photos.0');
    }

    public function test_non_image_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'PDF Gallery',
            'photos' => [$file],
        ])->assertSessionHasErrors('photos.0');
    }

    public function test_over_size_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('large.jpg', 800, 600)->size(6000);

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Large Gallery',
            'photos' => [$file],
        ])->assertSessionHasErrors('photos.0');
    }

    public function test_excessive_dimensions_rejected(): void
    {
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('wide.jpg', 9000, 100);

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Wide Gallery',
            'photos' => [$file],
        ])->assertSessionHasErrors('photos.0');
    }

    public function test_multiple_photos_uploaded_successfully(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $files = [
            UploadedFile::fake()->image('photo1.jpg', 800, 600),
            UploadedFile::fake()->image('photo2.jpg', 800, 600),
            UploadedFile::fake()->image('photo3.jpg', 800, 600),
        ];

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Multi Photo Gallery',
            'photos' => $files,
        ]);

        $gallery = Gallery::where('slug', 'multi-photo-gallery')->first();
        $this->assertCount(3, $gallery->getMedia('photos'));
    }

    public function test_alt_text_persists_as_custom_property(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Alt Text Gallery',
            'photos' => [$file],
            'alt_text' => 'Custom alt text for photo',
        ]);

        $gallery = Gallery::where('slug', 'alt-text-gallery')->first();
        $media = $gallery->getFirstMedia('photos');
        $this->assertSame('Custom alt text for photo', $media->getCustomProperty('alt_text'));
    }

    public function test_caption_persists_via_update(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Caption Gallery',
            'photos' => [$file],
        ]);

        $gallery = Gallery::where('slug', 'caption-gallery')->first();
        $mediaId = $gallery->getFirstMedia('photos')->id;

        $this->actingAs($user)->put(route('admin.galleries.update', $gallery), [
            'title' => $gallery->title,
            'description' => $gallery->description,
            'photo_caption' => [$mediaId => 'Updated caption text'],
        ]);

        $gallery->refresh();
        $media = $gallery->getMedia('photos')->firstWhere('id', $mediaId);
        $this->assertSame('Updated caption text', $media->getCustomProperty('caption'));
    }

    public function test_cover_selection_works(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $files = [
            UploadedFile::fake()->image('photo1.jpg', 800, 600),
            UploadedFile::fake()->image('photo2.jpg', 800, 600),
        ];

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Cover Gallery',
            'photos' => $files,
        ]);

        $gallery = Gallery::where('slug', 'cover-gallery')->first();
        $media = $gallery->getMedia('photos');
        $secondMediaId = $media[1]->id;

        $this->actingAs($user)->put(route('admin.galleries.update', $gallery), [
            'title' => $gallery->title,
            'description' => $gallery->description,
            'cover_media_id' => $secondMediaId,
        ]);

        $gallery->refresh();
        $cover = $gallery->getCoverImage();
        $this->assertSame($secondMediaId, $cover->id);
    }

    public function test_photo_removal_works(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Removal Gallery',
            'photos' => [$file],
        ]);

        $gallery = Gallery::where('slug', 'removal-gallery')->first();
        $mediaId = $gallery->getFirstMedia('photos')->id;

        $this->actingAs($user)->put(route('admin.galleries.update', $gallery), [
            'title' => $gallery->title,
            'description' => $gallery->description,
            'remove_photo' => $mediaId,
        ]);

        $gallery->refresh();
        $this->assertCount(0, $gallery->getMedia('photos'));
    }

    public function test_photo_reorder_works(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $files = [
            UploadedFile::fake()->image('first.jpg', 800, 600),
            UploadedFile::fake()->image('second.jpg', 800, 600),
            UploadedFile::fake()->image('third.jpg', 800, 600),
        ];

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Reorder Gallery',
            'photos' => $files,
        ]);

        $gallery = Gallery::where('slug', 'reorder-gallery')->first();
        $media = $gallery->getMedia('photos')->keyBy('id');

        $ids = $media->pluck('id')->toArray();
        $reordered = [$ids[2], $ids[0], $ids[1]];

        $this->actingAs($user)->patch(route('admin.galleries.reorder', $gallery), [
            'order' => $reordered,
        ])->assertRedirect();

        $gallery->refresh();
        $newOrder = $gallery->getMedia('photos')->pluck('id')->toArray();
        $this->assertSame($reordered, $newOrder);
    }

    public function test_photo_belonging_to_wrong_gallery_rejected(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();

        $gallery1 = Gallery::factory()->create(['created_by' => $user->id]);
        $gallery2 = Gallery::factory()->create(['created_by' => $user->id]);

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $gallery1->addMedia($file)->toMediaCollection('photos');
        $mediaId = $gallery1->getFirstMedia('photos')->id;

        $this->actingAs($user)->put(route('admin.galleries.update', $gallery2), [
            'title' => $gallery2->title,
            'description' => $gallery2->description,
            'remove_photo' => $mediaId,
        ]);

        $gallery1->refresh();
        $this->assertCount(1, $gallery1->getMedia('photos'));
    }

    public function test_force_delete_cleans_media(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();

        $gallery = Gallery::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $gallery->addMedia($file)->toMediaCollection('photos');

        $mediaId = $gallery->getFirstMedia('photos')->id;
        $gallery->delete();

        $this->actingAs($user)->delete(route('admin.galleries.force-delete', $gallery));

        $this->assertDatabaseMissing('galleries', ['id' => $gallery->id]);
        $this->assertDatabaseMissing('media', ['id' => $mediaId]);
    }

    public function test_reorder_rejects_duplicate_ids(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $files = [
            UploadedFile::fake()->image('first.jpg', 800, 600),
            UploadedFile::fake()->image('second.jpg', 800, 600),
        ];

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Reorder Dup Gallery',
            'photos' => $files,
        ]);

        $gallery = Gallery::where('slug', 'reorder-dup-gallery')->first();
        $id = $gallery->getFirstMedia('photos')->id;

        $this->actingAs($user)->patch(route('admin.galleries.reorder', $gallery), [
            'order' => [$id, $id],
        ])->assertSessionHasErrors('order');
    }

    public function test_reorder_rejects_foreign_media_id(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();

        $gallery1 = Gallery::factory()->create(['created_by' => $user->id]);
        $gallery2 = Gallery::factory()->create(['created_by' => $user->id]);

        $file1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('photo2.jpg', 800, 600);
        $gallery1->addMedia($file1)->toMediaCollection('photos');
        $gallery2->addMedia($file2)->toMediaCollection('photos');

        $foreignId = $gallery1->getFirstMedia('photos')->id;
        $ownId = $gallery2->getFirstMedia('photos')->id;

        $this->actingAs($user)->patch(route('admin.galleries.reorder', $gallery2), [
            'order' => [$ownId, $foreignId],
        ])->assertSessionHasErrors('order');
    }

    public function test_reorder_rejects_wrong_collection_media(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();

        $gallery = Gallery::factory()->create(['created_by' => $user->id]);
        $file = UploadedFile::fake()->image('portrait.jpg', 800, 600);
        $gallery->addMedia($file)->toMediaCollection('featured');

        $mediaId = $gallery->getFirstMedia('featured')->id;

        $this->actingAs($user)->patch(route('admin.galleries.reorder', $gallery), [
            'order' => [$mediaId],
        ])->assertSessionHasErrors('order');
    }

    public function test_cover_unsets_previous_cover(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $files = [
            UploadedFile::fake()->image('photoA.jpg', 800, 600),
            UploadedFile::fake()->image('photoB.jpg', 800, 600),
        ];

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Cover Switch Gallery',
            'photos' => $files,
        ]);

        $gallery = Gallery::where('slug', 'cover-switch-gallery')->first();
        $media = $gallery->getMedia('photos');
        $mediaAId = $media[0]->id;
        $mediaBId = $media[1]->id;

        $this->actingAs($user)->put(route('admin.galleries.update', $gallery), [
            'title' => $gallery->title,
            'description' => $gallery->description,
            'cover_media_id' => $mediaAId,
        ]);

        $gallery->refresh();
        $this->assertTrue($gallery->getMedia('photos')->firstWhere('id', $mediaAId)->getCustomProperty('is_cover'));

        $this->actingAs($user)->put(route('admin.galleries.update', $gallery), [
            'title' => $gallery->title,
            'description' => $gallery->description,
            'cover_media_id' => $mediaBId,
        ]);

        $gallery->refresh();
        $this->assertTrue($gallery->getMedia('photos')->firstWhere('id', $mediaBId)->getCustomProperty('is_cover'));
        $this->assertFalse($gallery->getMedia('photos')->firstWhere('id', $mediaAId)->getCustomProperty('is_cover'));
    }

    public function test_cover_fallback_after_delete(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $files = [
            UploadedFile::fake()->image('cover.jpg', 800, 600),
            UploadedFile::fake()->image('second.jpg', 800, 600),
        ];

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Cover Fallback Gallery',
            'photos' => $files,
        ]);

        $gallery = Gallery::where('slug', 'cover-fallback-gallery')->first();
        $media = $gallery->getMedia('photos');
        $coverId = $media[0]->id;
        $secondId = $media[1]->id;

        $this->actingAs($user)->put(route('admin.galleries.update', $gallery), [
            'title' => $gallery->title,
            'description' => $gallery->description,
            'cover_media_id' => $coverId,
        ]);

        $this->actingAs($user)->put(route('admin.galleries.update', $gallery), [
            'title' => $gallery->title,
            'description' => $gallery->description,
            'remove_photo' => $coverId,
        ]);

        $gallery->refresh();
        $cover = $gallery->getCoverImage();
        $this->assertNotNull($cover);
        $this->assertSame($secondId, $cover->id);
    }

    public function test_empty_gallery_cover_returns_null(): void
    {
        $gallery = Gallery::factory()->create();
        $this->assertNull($gallery->getCoverImage());
    }

    public function test_batch_upload_all_or_nothing(): void
    {
        Storage::fake('local');
        $user = $this->createAdminUser();
        $validFile = UploadedFile::fake()->image('valid.jpg', 800, 600);
        $invalidFile = UploadedFile::fake()->create('bad.svg', 100, 'image/svg+xml');

        $this->actingAs($user)->post(route('admin.galleries.store'), [
            'title' => 'Batch Gallery',
            'photos' => [$validFile, $invalidFile],
        ])->assertSessionHasErrors('photos.1');

        $gallery = Gallery::where('slug', 'batch-gallery')->first();
        if ($gallery) {
            $this->assertCount(0, $gallery->getMedia('photos'));
        }
    }
}
