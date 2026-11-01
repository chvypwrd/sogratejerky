<?php

namespace Tests\Feature\Public;

use App\Models\Faq;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_faq_index_returns_200(): void
    {
        $response = $this->get(route('faq.index'));

        $response->assertOk();
    }

    public function test_active_faqs_shown(): void
    {
        $faq = Faq::factory()
            ->active()
            ->create(['question' => 'What are your hours?']);

        $response = $this->get(route('faq.index'));

        $response->assertSee($faq->question);
    }

    public function test_inactive_faqs_hidden(): void
    {
        $faq = Faq::factory()
            ->inactive()
            ->create(['question' => 'Removed question?']);

        $response = $this->get(route('faq.index'));

        $response->assertDontSee('Removed question?');
    }

    public function test_category_grouping_present(): void
    {
        Faq::factory()
            ->active()
            ->create([
                'question' => 'Pricing FAQ?',
                'category' => 'Pricing',
            ]);

        Faq::factory()
            ->active()
            ->create([
                'question' => 'Schedule FAQ?',
                'category' => 'Schedule',
            ]);

        $response = $this->get(route('faq.index'));

        $response->assertSee('Pricing');
        $response->assertSee('Schedule');
    }

    public function test_accordion_ids_unique(): void
    {
        Faq::factory()
            ->active()
            ->create(['question' => 'First FAQ?']);
        Faq::factory()
            ->active()
            ->create(['question' => 'Second FAQ?']);

        $response = $this->get(route('faq.index'));

        $content = $response->getContent();
        preg_match_all('/id="(faq-\d+)"/', $content, $matches);
        $unique = array_unique($matches[1]);
        $this->assertCount(count($matches[1]), $unique, 'All accordion IDs should be unique');
    }

    public function test_faqs_start_collapsed_and_share_an_accordion_parent(): void
    {
        Faq::factory()->active()->create(['question' => 'First FAQ?', 'category' => 'General']);
        Faq::factory()->active()->create(['question' => 'Second FAQ?', 'category' => 'Support']);

        $response = $this->get(route('faq.index'));

        $response->assertOk();
        $response->assertDontSee('aria-expanded="true"', false);
        $response->assertDontSee('accordion-collapse collapse show', false);
        $response->assertSee('data-bs-parent="#faq-accordion"', false);
    }

    public function test_answers_sanitized(): void
    {
        $faq = Faq::factory()
            ->active()
            ->create([
                'question' => 'XSS FAQ?',
                'answer' => '<p>Safe answer</p><script>alert("xss")</script>',
            ]);

        $response = $this->get(route('faq.index'));

        $response->assertSee('Safe answer');
    }

    public function test_link_to_home_present(): void
    {
        $response = $this->get(route('faq.index'));

        $response->assertSee(route('home'));
    }
}
