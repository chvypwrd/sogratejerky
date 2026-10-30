<?php

namespace Tests\Feature\Production;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CspTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_does_not_have_csp_header(): void
    {
        $response = $this->get('/');

        $response->assertHeaderMissing('Content-Security-Policy');
    }
}
