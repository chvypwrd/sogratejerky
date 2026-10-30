<?php

namespace Tests\Feature\Cms;

use App\Models\Faq;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_faq_index_returns_200(): void
    {
        Faq::factory()->active()->create();

        $this->get(route('faq.index'))->assertOk();
    }

    public function test_active_faqs_shown(): void
    {
        Faq::factory()->active()->create([
            'question' => 'What are your hours?',
        ]);

        $this->get(route('faq.index'))
            ->assertOk()
            ->assertSee('What are your hours?');
    }

    public function test_inactive_faqs_hidden(): void
    {
        Faq::factory()->inactive()->create([
            'question' => 'Inactive question?',
        ]);

        $this->get(route('faq.index'))
            ->assertOk()
            ->assertDontSee('Inactive question?');
    }

    public function test_trashed_faqs_hidden(): void
    {
        $faq = Faq::factory()->active()->create([
            'question' => 'Trashed question?',
        ]);
        $faq->delete();

        $this->get(route('faq.index'))
            ->assertOk()
            ->assertDontSee('Trashed question?');
    }

    public function test_faqs_grouped_by_category(): void
    {
        Faq::factory()->active()->create([
            'question' => 'Class hours?',
            'category' => 'Schedule',
        ]);
        Faq::factory()->active()->create([
            'question' => 'Class prices?',
            'category' => 'Pricing',
        ]);
        Faq::factory()->active()->create([
            'question' => 'Another schedule question?',
            'category' => 'Schedule',
        ]);

        $response = $this->get(route('faq.index'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('Schedule', $content);
        $this->assertStringContainsString('Pricing', $content);
    }

    public function test_accordion_ids_unique(): void
    {
        Faq::factory()->active()->create([
            'question' => 'First question?',
            'category' => 'General',
        ]);
        Faq::factory()->active()->create([
            'question' => 'Second question?',
            'category' => 'General',
        ]);

        $response = $this->get(route('faq.index'));
        $content = $response->getContent();

        preg_match_all('/id="faq-(\d+)"/', $content, $matches);
        $ids = $matches[1];

        $this->assertCount(2, $ids);
        $this->assertCount(2, array_unique($ids));
    }

    public function test_seo_present(): void
    {
        $response = $this->get(route('faq.index'));
        $response->assertOk();
        $response->assertSee('Frequently Asked Questions');
    }
}
