<?php

namespace Tests\Feature\Cms;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PagePublicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_draft_page_not_visible_publicly(): void
    {
        $page = Page::factory()->draft()->create([
            'created_by' => null,
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertNotFound();
    }

    public function test_published_page_visible_publicly(): void
    {
        $page = Page::factory()->published()->create([
            'created_by' => null,
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee($page->title);
    }

    public function test_future_published_at_returns_404_until_time(): void
    {
        $page = Page::factory()->create([
            'status' => 'published',
            'published_at' => now()->addWeek(),
            'created_by' => null,
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertNotFound();
    }

    public function test_publish_sets_status_and_published_at(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->draft()->create([
            'created_by' => $user->id,
            'published_at' => null,
        ]);

        $this->actingAs($user)->post(route('admin.pages.publish', $page))
            ->assertRedirect();

        $page->refresh();
        $this->assertSame(PageStatus::Published, $page->status);
        $this->assertNotNull($page->published_at);
    }

    public function test_unpublish_clears_status_to_draft(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $page = Page::factory()->published()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->post(route('admin.pages.unpublish', $page))
            ->assertRedirect();

        $page->refresh();
        $this->assertSame(PageStatus::Draft, $page->status);
        $this->assertNull($page->published_at);
    }

    public function test_is_published_checks_both_status_and_date(): void
    {
        $page = Page::factory()->create(['status' => 'draft', 'published_at' => null]);
        $this->assertFalse($page->isPublished());

        $draftId = $page->id;
        DB::table('pages')->where('id', $draftId)->update(['status' => 'published', 'published_at' => null]);
        $page = Page::find($draftId);
        $this->assertFalse($page->isPublished());

        DB::table('pages')->where('id', $draftId)->update(['published_at' => now()->addWeek()->toDateTimeString()]);
        $page = Page::find($draftId);
        $this->assertFalse($page->isPublished());

        DB::table('pages')->where('id', $draftId)->update(['published_at' => now()->subWeek()->toDateTimeString()]);
        $page = Page::find($draftId);
        $this->assertTrue($page->isPublished());
    }

    public function test_published_scope_filters_correctly(): void
    {
        $published = Page::factory()->published()->create(['created_by' => null]);
        $draft = Page::factory()->draft()->create(['created_by' => null]);
        $future = Page::factory()->create([
            'status' => 'published',
            'published_at' => now()->addWeek()->timestamp,
            'created_by' => null,
        ]);

        $publishedSlugs = Page::published()->pluck('slug')->toArray();

        $this->assertContains($published->slug, $publishedSlugs);
        $this->assertNotContains($draft->slug, $publishedSlugs);
        $this->assertNotContains($future->slug, $publishedSlugs);
    }

    public function test_publishing_requires_pages_publish_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->draft()->create(['created_by' => $user->id]);

        $this->actingAs($user)->post(route('admin.pages.publish', $page))
            ->assertRedirect();

        $page->refresh();
        $this->assertSame(PageStatus::Published, $page->status);
    }

    public function test_crafted_request_cannot_bypass_publish_permission(): void
    {
        $user = User::factory()->create();

        $page = Page::factory()->draft()->create(['created_by' => $user->id]);

        $this->actingAs($user)->post(route('admin.pages.publish', $page))
            ->assertForbidden();

        $page->refresh();
        $this->assertSame(PageStatus::Draft, $page->status);
    }
}
