<?php

namespace Tests\Feature\Gallery;

use App\Models\Gallery;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryPublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_gallery_index_returns_200(): void
    {
        Gallery::factory()->active()->create();

        $this->get(route('gallery.index'))->assertOk();
    }

    public function test_active_galleries_shown(): void
    {
        Gallery::factory()->active()->create([
            'title' => 'Active Gallery',
        ]);

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertSee('Active Gallery');
    }

    public function test_inactive_galleries_hidden(): void
    {
        Gallery::factory()->inactive()->create([
            'title' => 'Inactive Gallery',
        ]);

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertDontSee('Inactive Gallery');
    }

    public function test_trashed_galleries_hidden(): void
    {
        $gallery = Gallery::factory()->active()->create([
            'title' => 'Trashed Gallery',
        ]);
        $gallery->delete();

        $this->get(route('gallery.index'))
            ->assertOk()
            ->assertDontSee('Trashed Gallery');
    }

    public function test_correct_sort_order(): void
    {
        Gallery::factory()->active()->create([
            'title' => 'Zebra Gallery',
            'sort_order' => 2,
        ]);
        Gallery::factory()->active()->create([
            'title' => 'Alpha Gallery',
            'sort_order' => 1,
        ]);
        Gallery::factory()->active()->create([
            'title' => 'Middle Gallery',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('gallery.index'));
        $response->assertOk();

        $content = $response->getContent();
        $alphaPos = strpos($content, 'Alpha Gallery');
        $middlePos = strpos($content, 'Middle Gallery');
        $zebraPos = strpos($content, 'Zebra Gallery');

        $this->assertNotFalse($alphaPos);
        $this->assertNotFalse($middlePos);
        $this->assertNotFalse($zebraPos);
        $this->assertLessThan($middlePos, $alphaPos);
        $this->assertLessThan($zebraPos, $middlePos);
    }

    public function test_empty_gallery_index_returns_200_with_friendly_message(): void
    {
        $response = $this->get(route('gallery.index'));
        $response->assertOk();
        $response->assertSee('No galleries available at this time. Please check back soon!');
    }

    public function test_gallery_detail_returns_200_for_active_gallery(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'active-detail-gallery',
        ]);

        $this->get(route('gallery.show', 'active-detail-gallery'))->assertOk();
    }

    public function test_gallery_detail_returns_404_for_inactive_gallery(): void
    {
        Gallery::factory()->inactive()->create([
            'slug' => 'inactive-detail-gallery',
        ]);

        $this->get(route('gallery.show', 'inactive-detail-gallery'))->assertNotFound();
    }

    public function test_gallery_detail_returns_404_for_trashed_gallery(): void
    {
        $gallery = Gallery::factory()->active()->create([
            'slug' => 'trashed-detail-gallery',
        ]);
        $gallery->delete();

        $this->get(route('gallery.show', 'trashed-detail-gallery'))->assertNotFound();
    }

    public function test_photos_ordered_correctly(): void
    {
        Storage::fake('local');

        $gallery = Gallery::factory()->active()->create([
            'slug' => 'ordered-photos-gallery',
        ]);

        $file1 = UploadedFile::fake()->image('photo1.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('photo2.jpg', 800, 600);
        $file3 = UploadedFile::fake()->image('photo3.jpg', 800, 600);

        $gallery->addMedia($file1)->withCustomProperties(['alt_text' => 'Third'])->toMediaCollection('photos');
        $gallery->addMedia($file2)->withCustomProperties(['alt_text' => 'First'])->toMediaCollection('photos');
        $gallery->addMedia($file3)->withCustomProperties(['alt_text' => 'Second'])->toMediaCollection('photos');

        $response = $this->get(route('gallery.show', 'ordered-photos-gallery'));
        $response->assertOk();
    }

    public function test_captions_escaped_in_output(): void
    {
        Storage::fake('local');

        $gallery = Gallery::factory()->active()->create([
            'slug' => 'escaped-caption-gallery',
        ]);

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $gallery->addMedia($file)
            ->withCustomProperties(['caption' => '<script>alert("xss")</script>'])
            ->toMediaCollection('photos');

        $response = $this->get(route('gallery.show', 'escaped-caption-gallery'));
        $response->assertOk();
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_alt_text_escaped_in_output(): void
    {
        Storage::fake('local');

        $gallery = Gallery::factory()->active()->create([
            'slug' => 'escaped-alt-gallery',
        ]);

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $gallery->addMedia($file)
            ->withCustomProperties(['alt_text' => '<img src=x onerror=alert(1)>'])
            ->toMediaCollection('photos');

        $response = $this->get(route('gallery.show', 'escaped-alt-gallery'));
        $response->assertOk();
        $response->assertSee('&amp;lt;img', false);
        $response->assertDontSee('<img src=x onerror=alert(1)>');
    }

    public function test_empty_gallery_detail_returns_200(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'empty-gallery-detail',
        ]);

        $response = $this->get(route('gallery.show', 'empty-gallery-detail'));
        $response->assertOk();
        $response->assertSee('No photos in this gallery yet.');
    }
}
