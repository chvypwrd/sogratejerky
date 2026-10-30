<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_can_boot(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
    }

    public function test_application_name_is_configured(): void
    {
        $this->assertEquals(
            'SOGRATE',
            config('app.name')
        );
    }
}
