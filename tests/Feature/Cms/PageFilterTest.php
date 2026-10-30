<?php

namespace Tests\Feature\Cms;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function createEditor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        return $user;
    }

    public function test_default_shows_all_non_trashed(): void
    {
        $user = $this->createEditor();

        $draft = Page::factory()->draft()->create(['created_by' => $user->id]);
        $published = Page::factory()->published()->create(['created_by' => $user->id]);
        $trashed = Page::factory()->create(['created_by' => $user->id]);
        $trashed->delete();

        $response = $this->actingAs($user)->get(route('admin.pages.index'));
        $response->assertOk();
        $response->assertSee($draft->title);
        $response->assertSee($published->title);
        $response->assertDontSee($trashed->title);
    }

    public function test_filter_draft_shows_only_drafts(): void
    {
        $user = $this->createEditor();

        $draft = Page::factory()->draft()->create(['created_by' => $user->id]);
        $published = Page::factory()->published()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('admin.pages.index', ['filter' => 'draft']));
        $response->assertOk();
        $response->assertSee($draft->title);
        $response->assertDontSee($published->title);
    }

    public function test_filter_published_shows_only_published(): void
    {
        $user = $this->createEditor();

        $draft = Page::factory()->draft()->create(['created_by' => $user->id]);
        $published = Page::factory()->published()->create(['created_by' => $user->id]);

        $response = $this->actingAs($user)->get(route('admin.pages.index', ['filter' => 'published']));
        $response->assertOk();
        $response->assertDontSee($draft->title);
        $response->assertSee($published->title);
    }

    public function test_filter_trash_shows_only_trashed(): void
    {
        $user = $this->createEditor();

        $active = Page::factory()->create(['created_by' => $user->id]);
        $trashed = Page::factory()->create(['created_by' => $user->id]);
        $trashed->delete();

        $response = $this->actingAs($user)->get(route('admin.pages.index', ['filter' => 'trash']));
        $response->assertOk();
        $response->assertDontSee($active->title);
        $response->assertSee($trashed->title);
    }
}
