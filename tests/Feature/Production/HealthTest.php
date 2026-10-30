<?php

namespace Tests\Feature\Production;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_endpoint_returns_200(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
    }

    public function test_up_endpoint_returns_html_or_json(): void
    {
        $response = $this->get('/up');

        $contentType = $response->headers->get('Content-Type');

        $this->assertStringContainsString('text/html', $contentType);
    }

    public function test_up_endpoint_does_not_expose_secrets(): void
    {
        $response = $this->get('/up');
        $content = $response->getContent();

        $this->assertStringNotContainsString(config('app.key'), $content);
        $this->assertStringNotContainsString(config('database.connections.mysql.username', ''), $content);
        $this->assertStringNotContainsString(config('database.connections.mysql.host', ''), $content);
    }

    public function test_up_endpoint_accessible_without_auth(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
    }
}
