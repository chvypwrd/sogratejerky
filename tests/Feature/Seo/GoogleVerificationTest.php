<?php

namespace Tests\Feature\Seo;

use App\Services\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GoogleVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_blank_verification_setting_does_not_render_meta(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('google-site-verification', false);
    }

    public function test_configured_token_renders_exact_meta_tag(): void
    {
        app(SettingsService::class)->set('seo.google_site_verification', 'ABC123');
        Cache::flush();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('<meta name="google-site-verification" content="ABC123">', false);
    }

    public function test_hostile_token_cannot_inject_html(): void
    {
        $hostileToken = '</script><script>alert(1)</script>';
        app(SettingsService::class)->set('seo.google_site_verification', $hostileToken);
        Cache::flush();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('</script><script>alert(1)</script>', false);
    }

    public function test_exactly_one_verification_tag_emitted(): void
    {
        app(SettingsService::class)->set('seo.google_site_verification', 'ABC123');
        Cache::flush();

        $response = $this->get(route('home'));
        $content = $response->getContent();

        $count = substr_count($content, 'google-site-verification');

        $this->assertSame(1, $count, 'Expected exactly one google-site-verification meta tag');
    }
}
