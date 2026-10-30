<?php

namespace Tests\Feature\Cms;

use App\Models\Instructor;
use App\Models\Redirect;
use App\Services\PageSlugRedirectService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstructorPublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_instructors_index_returns_200(): void
    {
        Instructor::factory()->active()->create();

        $this->get(route('instructors.index'))->assertOk();
    }

    public function test_active_instructors_shown(): void
    {
        Instructor::factory()->active()->create([
            'name' => 'Coach Sarah',
        ]);

        $this->get(route('instructors.index'))
            ->assertOk()
            ->assertSee('Coach Sarah');
    }

    public function test_inactive_instructors_hidden(): void
    {
        Instructor::factory()->inactive()->create([
            'name' => 'Inactive Coach',
        ]);

        $this->get(route('instructors.index'))
            ->assertOk()
            ->assertDontSee('Inactive Coach');
    }

    public function test_trashed_instructors_hidden(): void
    {
        $instructor = Instructor::factory()->active()->create([
            'name' => 'Trashed Coach',
        ]);
        $instructor->delete();

        $this->get(route('instructors.index'))
            ->assertOk()
            ->assertDontSee('Trashed Coach');
    }

    public function test_detail_active_returns_200(): void
    {
        $instructor = Instructor::factory()->active()->create([
            'slug' => 'active-instructor-detail',
        ]);

        $this->get(route('instructors.show', 'active-instructor-detail'))->assertOk();
    }

    public function test_detail_inactive_returns_404(): void
    {
        $instructor = Instructor::factory()->inactive()->create([
            'slug' => 'inactive-instructor-detail',
        ]);

        $this->get(route('instructors.show', 'inactive-instructor-detail'))->assertNotFound();
    }

    public function test_detail_trashed_returns_404(): void
    {
        $instructor = Instructor::factory()->active()->create([
            'slug' => 'trashed-instructor-detail',
        ]);
        $instructor->delete();

        $this->get(route('instructors.show', 'trashed-instructor-detail'))->assertNotFound();
    }

    public function test_ordering_correct(): void
    {
        Instructor::factory()->active()->create([
            'name' => 'Zebra Coach',
            'sort_order' => 2,
        ]);
        Instructor::factory()->active()->create([
            'name' => 'Alpha Coach',
            'sort_order' => 1,
        ]);
        Instructor::factory()->active()->create([
            'name' => 'Middle Coach',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('instructors.index'));
        $response->assertOk();

        $content = $response->getContent();
        $alphaPos = strpos($content, 'Alpha Coach');
        $middlePos = strpos($content, 'Middle Coach');
        $zebraPos = strpos($content, 'Zebra Coach');

        $this->assertNotFalse($alphaPos);
        $this->assertNotFalse($middlePos);
        $this->assertNotFalse($zebraPos);
        $this->assertLessThan($middlePos, $alphaPos);
        $this->assertLessThan($zebraPos, $middlePos);
    }

    public function test_seo_metadata_present(): void
    {
        $instructor = Instructor::factory()->active()->create([
            'slug' => 'seo-test-instructor',
            'meta_title' => 'SEO Title for Instructor',
            'meta_description' => 'This is a meta description for the instructor.',
        ]);

        $response = $this->get(route('instructors.show', 'seo-test-instructor'));
        $response->assertOk();
        $response->assertSee('SEO Title for Instructor');
        $response->assertSee('This is a meta description for the instructor.');
        $response->assertSee('<meta name="description"', false);
    }

    public function test_featured_image_rendered(): void
    {
        Storage::fake('local');

        $instructor = Instructor::factory()->active()->create([
            'slug' => 'image-test-instructor',
        ]);

        $file = UploadedFile::fake()->image('portrait.jpg', 800, 600);
        $instructor->addMedia($file)->toMediaCollection('portrait');

        $response = $this->get(route('instructors.show', 'image-test-instructor'));
        $response->assertOk();
        $response->assertSee('portrait.jpg', false);
    }

    public function test_alt_text_rendered(): void
    {
        Storage::fake('local');

        $instructor = Instructor::factory()->active()->create([
            'slug' => 'alt-text-instructor',
        ]);

        $file = UploadedFile::fake()->image('portrait.jpg', 800, 600);
        $instructor->addMedia($file)
            ->withCustomProperties(['alt_text' => 'Coach portrait photo'])
            ->toMediaCollection('portrait');

        $response = $this->get(route('instructors.show', 'alt-text-instructor'));
        $response->assertOk();
        $response->assertSee('Coach portrait photo', false);
    }

    public function test_slug_redirect_resolves_via_service(): void
    {
        Redirect::create([
            'old_path' => '/instructors/old-instructor-slug',
            'new_path' => '/instructors/new-instructor-slug',
            'status_code' => 301,
            'active' => true,
        ]);

        $result = app(PageSlugRedirectService::class)
            ->resolve('/instructors/old-instructor-slug');

        $this->assertNotNull($result);
        $this->assertSame('/instructors/new-instructor-slug', $result['to']);
        $this->assertSame(301, $result['status']);
    }
}
