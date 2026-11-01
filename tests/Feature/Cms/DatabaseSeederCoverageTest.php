<?php

namespace Tests\Feature\Cms;

use App\Enums\MenuLocation;
use App\Enums\PageStatus;
use App\Models\Faq;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_database_seed_produces_expected_public_content_and_navigation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(PageStatus::Published, Page::where('slug', 'about')->firstOrFail()->status);
        $this->assertSame(5, Page::count());

        foreach (['privacy-policy', 'terms-and-conditions', 'shipping-policy', 'refund-policy'] as $slug) {
            $this->assertSame(PageStatus::Draft, Page::where('slug', $slug)->firstOrFail()->status);
        }

        $this->assertSame(6, Faq::count());
        $this->assertSame(5, Faq::where('active', true)->count());

        $about = Page::where('slug', 'about')->firstOrFail();
        $privacy = Page::where('slug', 'privacy-policy')->firstOrFail();

        $this->assertTrue(MenuItem::where('menu_id', $this->menuId(MenuLocation::Primary))->where('page_id', $about->id)->exists());
        $this->assertTrue(MenuItem::where('menu_id', $this->menuId(MenuLocation::Footer))->where('page_id', $about->id)->exists());
        $this->assertTrue(MenuItem::where('menu_id', $this->menuId(MenuLocation::Footer))->where('page_id', $privacy->id)->exists());

        $this->get(route('pages.show', 'about'))->assertOk();
        $this->get(route('pages.show', 'privacy-policy'))->assertNotFound();
        $this->get(route('contact.show'))->assertOk();
        $this->get(route('faq.index'))->assertOk();
        $this->assertSame(0, User::count());
    }

    public function test_repeated_database_seeding_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $pageCount = Page::count();
        $faqCount = Faq::count();
        $menuItemCount = MenuItem::count();
        $pageIds = Page::orderBy('id')->pluck('id')->all();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($pageCount, Page::count());
        $this->assertSame($faqCount, Faq::count());
        $this->assertSame($menuItemCount, MenuItem::count());
        $this->assertSame($pageIds, Page::orderBy('id')->pluck('id')->all());
    }

    private function menuId(MenuLocation $location): int
    {
        return Menu::where('location', $location->value)->firstOrFail()->id;
    }
}
