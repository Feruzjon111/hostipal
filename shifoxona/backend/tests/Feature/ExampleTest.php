<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertJsonPath('name', 'Shifo API');
    }

    public function test_unknown_api_urls_return_json_404_instead_of_the_spa(): void
    {
        $this->getJson('/api/does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_frontend_can_request_a_csrf_token(): void
    {
        $this->getJson('/api/csrf')->assertOk()->assertJsonStructure(['csrf_token']);
    }
}
