<?php

namespace Tests\Feature\Cms;

use App\Enums\RobotsDirective;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PageSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_title_fallback_to_page_title(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'meta_title' => null,
            'title' => 'Fallback Title',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('Fallback Title', false);
    }

    public function test_description_in_meta_tag(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'meta_description' => 'This is a test description for SEO.',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('This is a test description for SEO.', false);
    }

    public function test_canonical_fallback_to_route_url(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'canonical_url' => null,
        ]);

        $response = $this->get(route('pages.show', $page->slug));
        $response->assertOk();

        $canonicalUrl = route('pages.show', $page->slug, false);
        $response->assertSee('rel="canonical"', false);
        $response->assertSee(e($canonicalUrl), false);
    }

    public function test_explicit_canonical_override(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'canonical_url' => 'https://example.com/custom-canonical',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('https://example.com/custom-canonical', false);
    }

    public function test_robots_directive(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'robots' => RobotsDirective::IndexFollow,
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('index,follow', false);
    }

    public function test_og_type_in_meta(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('og:type', false)
            ->assertSee('website', false);
    }

    public function test_og_title_in_meta(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'meta_title' => 'Custom OG Title',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('og:title', false)
            ->assertSee('Custom OG Title', false);
    }

    public function test_og_description_in_meta(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'meta_description' => 'OG description content',
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('og:description', false)
            ->assertSee('OG description content', false);
    }

    public function test_og_url_in_meta(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
        ]);

        $response = $this->get(route('pages.show', $page->slug));
        $response->assertOk();
        $response->assertSee('og:url', false);
    }

    public function test_og_image_from_featured_media(): void
    {
        Storage::fake('local');

        $page = Page::factory()->published()->create([
            'created_by' => null,
        ]);

        $file = UploadedFile::fake()->image('og-image.jpg', 1200, 630);
        $page->addMedia($file)->toMediaCollection('featured');

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('og:image', false);
    }

    public function test_metadata_escaped_hostile_values(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'meta_title' => '<script>alert("xss")</script>',
            'meta_description' => '"><img src=x onerror=alert(1)>',
        ]);

        $response = $this->get(route('pages.show', $page->slug));
        $response->assertOk();
        $response->assertDontSee('<script>', false);
        $response->assertSee('&lt;script&gt;', false);
        $response->assertSee('onerror', false);
    }

    public function test_preview_has_noindex_nofollow(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $user->id]);

        $signedUrl = URL::temporarySignedRoute(
            'pages.preview',
            now()->addMinutes(30),
            ['page' => $page->slug]
        );

        $this->actingAs($user)->get($signedUrl)
            ->assertOk()
            ->assertSee('noindex,nofollow', false);
    }
}
