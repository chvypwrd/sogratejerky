<?php

namespace Tests\Feature\Public;

use App\Models\Faq;
use App\Models\Testimonial;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_homepage_returns_200(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    public function test_hero_headline_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Texas Made. Seriously Good Jerky.');
    }

    public function test_contact_cta_links_to_contact_show(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee(route('contact.show'));
    }

    public function test_active_featured_testimonial_shown(): void
    {
        $testimonial = Testimonial::factory()
            ->active()
            ->featured()
            ->create(['name' => 'Happy Member']);

        $response = $this->get(route('home'));

        $response->assertSee($testimonial->name);
    }

    public function test_inactive_testimonial_not_shown(): void
    {
        $testimonial = Testimonial::factory()
            ->inactive()
            ->create(['name' => 'Hidden Reviewer']);

        $response = $this->get(route('home'));

        $response->assertDontSee($testimonial->name);
    }

    public function test_testimonial_html_escaped(): void
    {
        $testimonial = Testimonial::factory()
            ->active()
            ->featured()
            ->create([
                'name' => 'Test <script>alert("xss")</script>',
                'quote' => 'Quote with <img src=x onerror=alert(1)> tag',
            ]);

        $response = $this->get(route('home'));

        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertDontSee('<img src=x onerror=alert(1)>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_active_faq_shown_in_preview(): void
    {
        $faq = Faq::factory()
            ->active()
            ->create(['question' => 'How should I store my jerky?']);

        $response = $this->get(route('home'));

        $response->assertSee($faq->question);
    }

    public function test_inactive_faq_not_shown(): void
    {
        $faq = Faq::factory()
            ->inactive()
            ->create(['question' => 'Hidden FAQ Question?']);

        $response = $this->get(route('home'));

        $response->assertDontSee('Hidden FAQ Question?');
    }

    public function test_faq_all_link_correct(): void
    {
        Faq::factory()->active()->create();

        $response = $this->get(route('home'));

        $response->assertSee(route('faq.index'));
    }

    public function test_site_setting_name_reflected(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Sograte');
    }

    public function test_graceful_behavior_with_empty_database(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('error');
        $response->assertDontSee('broken');
    }

    public function test_main_landmark_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('id="main-content"', false);
    }

    public function test_skip_link_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Skip to main content');
    }
}
