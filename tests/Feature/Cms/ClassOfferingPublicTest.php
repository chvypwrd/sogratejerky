<?php

namespace Tests\Feature\Cms;

use App\Models\ClassOffering;
use App\Models\Redirect;
use App\Services\PageSlugRedirectService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClassOfferingPublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_classes_index_returns_200(): void
    {
        ClassOffering::factory()->active()->create();

        $this->get(route('classes.index'))->assertOk();
    }

    public function test_active_classes_shown(): void
    {
        ClassOffering::factory()->active()->create([
            'title' => 'Active Kickboxing',
        ]);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertSee('Active Kickboxing');
    }

    public function test_inactive_classes_hidden(): void
    {
        ClassOffering::factory()->inactive()->create([
            'title' => 'Inactive Class',
        ]);

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertDontSee('Inactive Class');
    }

    public function test_trashed_classes_hidden(): void
    {
        $class = ClassOffering::factory()->active()->create([
            'title' => 'Trashed Class',
        ]);
        $class->delete();

        $this->get(route('classes.index'))
            ->assertOk()
            ->assertDontSee('Trashed Class');
    }

    public function test_detail_active_returns_200(): void
    {
        $class = ClassOffering::factory()->active()->create([
            'slug' => 'active-class-detail',
        ]);

        $this->get(route('classes.show', 'active-class-detail'))->assertOk();
    }

    public function test_detail_inactive_returns_404(): void
    {
        $class = ClassOffering::factory()->inactive()->create([
            'slug' => 'inactive-class-detail',
        ]);

        $this->get(route('classes.show', 'inactive-class-detail'))->assertNotFound();
    }

    public function test_detail_trashed_returns_404(): void
    {
        $class = ClassOffering::factory()->active()->create([
            'slug' => 'trashed-class-detail',
        ]);
        $class->delete();

        $this->get(route('classes.show', 'trashed-class-detail'))->assertNotFound();
    }

    public function test_ordering_correct(): void
    {
        ClassOffering::factory()->active()->create([
            'title' => 'Zebra Class',
            'sort_order' => 2,
        ]);
        ClassOffering::factory()->active()->create([
            'title' => 'Alpha Class',
            'sort_order' => 1,
        ]);
        ClassOffering::factory()->active()->create([
            'title' => 'Middle Class',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('classes.index'));
        $response->assertOk();

        $content = $response->getContent();
        $alphaPos = strpos($content, 'Alpha Class');
        $middlePos = strpos($content, 'Middle Class');
        $zebraPos = strpos($content, 'Zebra Class');

        $this->assertNotFalse($alphaPos);
        $this->assertNotFalse($middlePos);
        $this->assertNotFalse($zebraPos);
        $this->assertLessThan($middlePos, $alphaPos);
        $this->assertLessThan($zebraPos, $middlePos);
    }

    public function test_seo_metadata_present(): void
    {
        $class = ClassOffering::factory()->active()->create([
            'slug' => 'seo-test-class',
            'meta_title' => 'SEO Title for Class',
            'meta_description' => 'This is a meta description for the class.',
        ]);

        $response = $this->get(route('classes.show', 'seo-test-class'));
        $response->assertOk();
        $response->assertSee('SEO Title for Class');
        $response->assertSee('This is a meta description for the class.');
        $response->assertSee('<meta name="description"', false);
    }

    public function test_featured_image_rendered(): void
    {
        Storage::fake('local');

        $class = ClassOffering::factory()->active()->create([
            'slug' => 'image-test-class',
        ]);

        $file = UploadedFile::fake()->image('class-photo.jpg', 800, 600);
        $class->addMedia($file)->toMediaCollection('featured');

        $response = $this->get(route('classes.show', 'image-test-class'));
        $response->assertOk();
        $response->assertSee('class-photo.jpg', false);
    }

    public function test_alt_text_rendered(): void
    {
        Storage::fake('local');

        $class = ClassOffering::factory()->active()->create([
            'slug' => 'alt-text-class',
        ]);

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $class->addMedia($file)
            ->withCustomProperties(['alt_text' => 'Kickboxing class photo'])
            ->toMediaCollection('featured');

        $response = $this->get(route('classes.show', 'alt-text-class'));
        $response->assertOk();
        $response->assertSee('Kickboxing class photo', false);
    }

    public function test_slug_redirect_resolves_via_service(): void
    {
        Redirect::create([
            'old_path' => '/classes/old-class-slug',
            'new_path' => '/classes/new-class-slug',
            'status_code' => 301,
            'active' => true,
        ]);

        $result = app(PageSlugRedirectService::class)
            ->resolve('/classes/old-class-slug');

        $this->assertNotNull($result);
        $this->assertSame('/classes/new-class-slug', $result['to']);
        $this->assertSame(301, $result['status']);
    }
}
