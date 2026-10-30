<?php

namespace Tests\Feature\Production;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_includes_x_content_type_options(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_homepage_includes_referrer_policy(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_homepage_includes_x_frame_options(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_homepage_includes_permissions_policy_with_camera(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    }

    public function test_admin_login_includes_security_headers(): void
    {
        $response = $this->get('/admin/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    }

    public function test_sitemap_returns_correct_content_type(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_robots_returns_correct_content_type(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }
}
