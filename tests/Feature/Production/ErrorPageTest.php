<?php

namespace Tests\Feature\Production;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_page_returns_404_status(): void
    {
        $response = $this->get('/nonexistent-page-'.uniqid());

        $response->assertStatus(404);
    }

    public function test_404_page_contains_heading(): void
    {
        $rendered = View::make('errors.404')->render();

        $this->assertStringContainsString('Page Not Found', $rendered);
    }

    public function test_404_page_does_not_contain_stack_trace(): void
    {
        $rendered = View::make('errors.404')->render();

        $this->assertStringNotContainsString('Stack trace', $rendered);
        $this->assertStringNotContainsString('Exception', $rendered);
    }

    public function test_404_page_contains_navigation_link(): void
    {
        $rendered = View::make('errors.404')->render();

        $this->assertStringContainsString('Home', $rendered);
    }

    public function test_503_page_renders_without_database(): void
    {
        $rendered = View::make('errors.503')->render();

        $this->assertNotEmpty($rendered);
    }

    public function test_503_page_contains_brand_name(): void
    {
        $rendered = View::make('errors.503')->render();

        $this->assertStringContainsString('SOGRATE', $rendered);
    }

    public function test_503_page_does_not_contain_database_errors(): void
    {
        $rendered = View::make('errors.503')->render();

        $this->assertStringNotContainsString('SQLSTATE', $rendered);
        $this->assertStringNotContainsString('database', $rendered);
    }

    public function test_419_page_renders_safe_message(): void
    {
        $rendered = View::make('errors.419')->render();

        $this->assertNotEmpty($rendered);
        $this->assertStringContainsString('Session', $rendered);
        $this->assertStringNotContainsString('Stack trace', $rendered);
        $this->assertStringNotContainsString('Exception', $rendered);
    }

    public function test_429_page_renders_generic_message(): void
    {
        $rendered = View::make('errors.429')->render();

        $this->assertNotEmpty($rendered);
        $this->assertStringContainsString('Many', $rendered);
        $this->assertStringNotContainsString('cache key', $rendered);
        $this->assertStringNotContainsString('throttle', $rendered);
    }

    public function test_500_page_renders_generic_message(): void
    {
        $rendered = View::make('errors.500')->render();

        $this->assertNotEmpty($rendered);
        $this->assertStringContainsString('wrong', $rendered);
        $this->assertStringNotContainsString('Stack trace', $rendered);
        $this->assertStringNotContainsString('SQLSTATE', $rendered);
    }
}
