<?php

namespace Tests\Feature\Public;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_skip_link_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Skip to main content');
        $response->assertSee('href="#main-content"', false);
    }

    public function test_main_landmark_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('id="main-content"', false);
        $response->assertSee('<main', false);
    }

    public function test_primary_nav_renders(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Main navigation');
        $response->assertSee('navbar');
    }

    public function test_footer_renders(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('site-footer');
    }

    public function test_site_name_fallback_works_when_no_logo(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('SOGRATE');
    }

    public function test_footer_contains_copyright_year(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee(date('Y'));
        $response->assertSee('All rights reserved');
    }
}
