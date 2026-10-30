<?php

namespace Tests\Feature\Public;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_contact_page_returns_200(): void
    {
        $response = $this->get(route('contact.show'));

        $response->assertOk();
    }

    public function test_form_present(): void
    {
        $response = $this->get(route('contact.show'));

        $response->assertSee('contact-form');
        $response->assertSee('method="POST"', false);
        $response->assertSee(route('contact.store'));
    }

    public function test_contact_details_display_from_settings(): void
    {
        $response = $this->get(route('contact.show'));

        $response->assertOk();
        $response->assertSee('Contact Us');
    }

    public function test_labels_present(): void
    {
        $response = $this->get(route('contact.show'));

        $response->assertSee('Name');
        $response->assertSee('Email');
        $response->assertSee('Phone');
        $response->assertSee('Subject');
        $response->assertSee('Message');
        $response->assertSee('Send Message');
    }

    public function test_seo_component_present(): void
    {
        $response = $this->get(route('contact.show'));

        $response->assertSee('Contact Us');
        $response->assertSee('title');
    }

    public function test_success_message_after_submission(): void
    {
        $response = $this->post(route('contact.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'This is a test message with enough characters to pass validation.',
        ]);

        $response->assertRedirect(route('contact.show'));
        $response->assertSessionHas('status');
    }
}
