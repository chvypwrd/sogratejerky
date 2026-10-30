<?php

namespace Tests\Feature\Production;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_request_receives_x_request_id_header(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Request-ID');
    }

    public function test_x_request_id_is_non_empty(): void
    {
        $response = $this->get('/');

        $requestId = $response->headers->get('X-Request-ID');

        $this->assertNotEmpty($requestId);
    }

    public function test_two_separate_requests_receive_different_ids(): void
    {
        $response1 = $this->get('/');
        $response2 = $this->get('/');

        $id1 = $response1->headers->get('X-Request-ID');
        $id2 = $response2->headers->get('X-Request-ID');

        $this->assertNotEquals($id1, $id2);
    }

    public function test_client_provided_x_request_id_is_not_reflected(): void
    {
        $clientRequestId = 'test-custom-request-id-12345';

        $response = $this->withHeader('X-Request-ID', $clientRequestId)->get('/');

        $serverRequestId = $response->headers->get('X-Request-ID');

        $this->assertNotEquals($clientRequestId, $serverRequestId);
    }
}
