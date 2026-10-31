<?php

namespace Tests\Feature\Seo;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_robots_txt_returns_200(): void
    {
        $this->get(route('robots'))->assertOk();
    }

    public function test_content_type_is_text_plain(): void
    {
        $response = $this->get(route('robots'));

        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function test_production_mode_includes_allow(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $response = $this->get(route('robots'));

        $response->assertSee('Allow: /', false);
    }

    public function test_production_mode_includes_sitemap_url(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $response = $this->get(route('robots'));
        $content = $response->getContent();

        $this->assertStringContainsString('Sitemap:', $content);
        $this->assertStringContainsString(route('sitemap', [], false), $content);
    }

    public function test_production_mode_disallows_private_routes(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $response = $this->get(route('robots'));

        $response->assertSee('Disallow: /admin', false);
        $response->assertSee('Disallow: /checkout', false);
        $response->assertSee('Disallow: /account', false);
    }

    public function test_non_production_mode_includes_disallow(): void
    {
        $response = $this->get(route('robots'));

        $response->assertSee('Disallow: /', false);
    }

    public function test_no_html_in_response(): void
    {
        $response = $this->get(route('robots'));
        $content = $response->getContent();

        $this->assertDoesNotMatchRegularExpression('/<html[\s>]/i', $content);
        $this->assertDoesNotMatchRegularExpression('/<body[\s>]/i', $content);
        $this->assertDoesNotMatchRegularExpression('/<div[\s>]/i', $content);
    }
}
