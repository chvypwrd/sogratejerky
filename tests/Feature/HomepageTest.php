<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_returns_success(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
    }

    public function test_homepage_contains_site_name(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('SOGRATE');
    }

    public function test_homepage_contains_hero_headline(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Texas Made. Seriously Good Jerky.');
    }

    public function test_homepage_uses_layout(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Skip to main content');
        $response->assertSee('main-content');
    }

    public function test_homepage_handles_empty_database_gracefully(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('broken');
        $response->assertDontSee('error');
    }
}
