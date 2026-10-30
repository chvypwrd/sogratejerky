<?php

namespace Tests\Feature\Gallery;

use App\Models\Gallery;
use App\Models\Redirect;
use App\Models\User;
use App\Services\PageSlugRedirectService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryRedirectTest extends TestCase
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

    public function test_gallery_slug_change_creates_redirect(): void
    {
        $user = $this->createAdminUser();

        $gallery = Gallery::factory()->create([
            'slug' => 'original-gallery',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->put(route('admin.galleries.update', $gallery), [
            'title' => 'Renamed Gallery',
            'description' => '<p>Updated content</p>',
            'slug' => 'renamed-gallery',
        ])->assertRedirect();

        $redirect = Redirect::where('old_path', '/gallery/original-gallery')->first();

        $this->assertNotNull($redirect);
        $this->assertSame('/gallery/renamed-gallery', $redirect->new_path);
        $this->assertSame(301, $redirect->status_code);
        $this->assertTrue($redirect->active);
    }

    public function test_old_gallery_url_returns_301(): void
    {
        $service = app(PageSlugRedirectService::class);
        $service->createRedirect('/gallery/old-gallery', '/gallery/new-gallery');

        $this->get('/gallery/old-gallery')
            ->assertRedirect('/gallery/new-gallery', 301);
    }

    public function test_new_gallery_url_returns_200(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'new-gallery',
        ]);

        $this->get('/gallery/new-gallery')->assertOk();
    }

    public function test_old_gallery_url_not_in_sitemap(): void
    {
        $service = app(PageSlugRedirectService::class);
        $service->createRedirect('/gallery/old-gallery', '/gallery/new-gallery');

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertDontSee('/gallery/old-gallery', false);
    }

    public function test_new_gallery_url_in_sitemap(): void
    {
        Gallery::factory()->active()->create([
            'slug' => 'new-gallery',
        ]);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertSee('/gallery/new-gallery', false);
    }
}
