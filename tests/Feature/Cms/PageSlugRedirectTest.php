<?php

namespace Tests\Feature\Cms;

use App\Models\Page;
use App\Models\Redirect;
use App\Services\PageSlugRedirectService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PageSlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        Route::bind('page', fn (string $value) => Page::withTrashed()->where('slug', $value)->firstOrFail());
    }

    public function test_slug_change_creates_301(): void
    {
        $service = app(PageSlugRedirectService::class);

        $page = Page::factory()->create([
            'created_by' => null,
            'slug' => 'original-slug',
        ]);

        $oldSlug = $page->slug;
        $page->update(['slug' => 'new-slug']);
        $service->handleSlugChange($page->fresh(), $oldSlug);

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/original-slug',
            'new_path' => '/new-slug',
            'status_code' => 301,
            'active' => true,
        ]);
    }

    public function test_old_path_redirects(): void
    {
        $service = app(PageSlugRedirectService::class);

        $page = Page::factory()->published()->create([
            'created_by' => null,
            'slug' => 'old-page',
        ]);

        $this->get(route('pages.show', 'old-page'))->assertOk();

        $oldSlug = $page->slug;
        $page->update(['slug' => 'new-page']);
        $service->handleSlugChange($page->fresh(), $oldSlug);

        $this->get(route('pages.show', 'old-page'))
            ->assertRedirect(route('pages.show', 'new-page', false));

        $this->get(route('pages.show', 'new-page'))->assertOk();
    }

    public function test_second_slug_change_creates_sequential_redirects(): void
    {
        $service = app(PageSlugRedirectService::class);

        $page = Page::factory()->create([
            'created_by' => null,
            'slug' => 'slug-a',
        ]);

        $oldSlug = $page->slug;
        $page->update(['slug' => 'slug-b']);
        $service->handleSlugChange($page->fresh(), $oldSlug);

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/slug-a',
            'new_path' => '/slug-b',
            'active' => true,
        ]);

        $oldSlug = $page->fresh()->slug;
        $page->update(['slug' => 'slug-c']);
        $service->handleSlugChange($page->fresh(), $oldSlug);

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/slug-b',
            'new_path' => '/slug-c',
            'active' => true,
        ]);
    }

    public function test_self_redirect_prevented(): void
    {
        $service = app(PageSlugRedirectService::class);

        $page = Page::factory()->create([
            'created_by' => null,
            'slug' => 'same-slug',
        ]);

        $service->handleSlugChange($page, 'same-slug');

        $this->assertDatabaseMissing('redirects', [
            'old_path' => '/same-slug',
        ]);
    }

    public function test_loop_prevented(): void
    {
        $service = app(PageSlugRedirectService::class);

        $service->createRedirect('/slug-a', '/slug-b');
        $service->createRedirect('/slug-b', '/slug-a');

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/slug-a',
            'new_path' => '/slug-b',
        ]);
    }

    public function test_inactive_redirect_ignored(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
            'slug' => 'inactive-test',
        ]);

        Redirect::create([
            'old_path' => '/inactive-test',
            'new_path' => '/somewhere-else',
            'status_code' => 301,
            'active' => false,
        ]);

        $this->get(route('pages.show', 'inactive-test'))->assertOk();
    }

    public function test_external_redirect_target_prevented(): void
    {
        $service = app(PageSlugRedirectService::class);

        $result = $service->normalizePath('https://evil.com/phishing');
        $this->assertStringStartsWith('/', $result);
    }

    public function test_query_and_fragment_handling_normalized_safely(): void
    {
        $service = app(PageSlugRedirectService::class);

        $result = $service->normalizePath('page-slug?query=1#fragment');
        $this->assertSame('/page-slug', $result);
    }
}
