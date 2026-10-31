<?php

namespace Tests\Feature\Cms;

use App\Enums\MenuLocation;
use App\Enums\PageStatus;
use App\Models\Faq;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\MenuService;
use Database\Seeders\LaunchContentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LaunchContentSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(LaunchContentSeeder::class);
        $this->seed(MenuSeeder::class);
        Cache::flush();
    }

    public function test_publishes_about_and_keeps_policy_pages_as_drafts(): void
    {
        $about = Page::where('slug', 'about')->firstOrFail();
        $this->assertSame(PageStatus::Published, $about->status);
        $this->get(route('pages.show', 'about'))->assertOk()->assertSee('About SOGRATE');

        foreach (['privacy-policy', 'terms-and-conditions', 'shipping-policy', 'refund-policy'] as $slug) {
            $page = Page::where('slug', $slug)->firstOrFail();
            $this->assertSame(PageStatus::Draft, $page->status);

            $this->get(route('pages.show', $slug))->assertNotFound();
        }
    }

    public function test_existing_contact_and_faq_routes_remain_public(): void
    {
        $this->get(route('contact.show'))->assertOk();
        $this->get(route('faq.index'))->assertOk();
    }

    public function test_known_faqs_are_active_and_refund_faq_remains_inactive(): void
    {
        $this->assertDatabaseHas('faqs', ['question' => 'How do I place an order?', 'active' => true]);
        $this->assertDatabaseHas('faqs', ['question' => 'How are refunds and cancellations handled?', 'active' => false]);
        $this->assertSame(5, Faq::where('active', true)->count());
    }

    public function test_seeder_is_idempotent_and_does_not_overwrite_edited_content(): void
    {
        $about = Page::where('slug', 'about')->firstOrFail();
        $about->update(['body' => '<p>Owner-edited about content.</p>']);

        $pageCount = Page::count();
        $faqCount = Faq::count();

        $this->seed(LaunchContentSeeder::class);

        $this->assertSame($pageCount, Page::count());
        $this->assertSame($faqCount, Faq::count());
        $this->assertSame('<p>Owner-edited about content.</p>', $about->fresh()->body);
    }

    public function test_navigation_includes_about_and_reveals_policy_links_after_publication(): void
    {
        $this->assertTrue(MenuItem::where('page_id', Page::where('slug', 'about')->value('id'))->exists());
        $this->assertTrue(MenuItem::where('page_id', Page::where('slug', 'privacy-policy')->value('id'))->exists());

        $primary = app(MenuService::class)->getRenderedMenu(MenuLocation::Primary);
        $this->assertStringContainsString('/about', $primary);
        $this->assertStringNotContainsString('/privacy-policy', $primary);

        $this->get(route('home'))->assertOk()->assertDontSee('/privacy-policy', false);

        Page::where('slug', 'privacy-policy')->firstOrFail()->publish();

        $this->get(route('home'))->assertOk()->assertSee('/privacy-policy', false);
    }

    public function test_draft_policies_are_excluded_from_sitemap(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk()
            ->assertSee('/about', false)
            ->assertDontSee('/privacy-policy', false);
    }
}
