<?php

namespace Tests\Feature\Contact;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Notifications\NewContactMessageNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_get_contact_returns_200_with_form(): void
    {
        $response = $this->get(route('contact.show'));

        $response->assertOk();
        $response->assertSee('contact-form');
        $response->assertSee('Send Message');
    }

    public function test_post_contact_with_valid_data_creates_contact_message(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'I would like to know more about your jerky.',
        ];

        $response = $this->post(route('contact.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('contact_messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry',
            'message' => 'I would like to know more about your jerky.',
            'status' => 'new',
        ]);
    }

    public function test_post_contact_with_valid_data_sends_notification(): void
    {
        Notification::fake();

        $data = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'subject' => 'Question',
            'message' => 'What are your opening hours please?',
        ];

        $this->post(route('contact.store'), $data);

        Notification::assertSentToTimes(
            new AnonymousNotifiable,
            NewContactMessageNotification::class,
            1
        );
    }

    public function test_post_contact_with_valid_data_redirects_with_success(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Hello',
            'message' => 'Just saying hello to the team.',
        ];

        $response = $this->post(route('contact.store'), $data);

        $response->assertRedirect(route('contact.show'));
    }

    public function test_post_contact_with_invalid_data_returns_validation_errors(): void
    {
        $response = $this->post(route('contact.store'), [
            'name' => '',
            'email' => 'bad',
            'message' => 'short',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'message']);
    }

    public function test_post_contact_with_missing_name_returns_validation_error(): void
    {
        $response = $this->post(route('contact.store'), [
            'email' => 'test@example.com',
            'message' => 'This is a test message body.',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_post_contact_with_invalid_email_returns_validation_error(): void
    {
        $response = $this->post(route('contact.store'), [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'message' => 'This is a test message body.',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_post_contact_with_message_too_short_returns_validation_error(): void
    {
        $response = $this->post(route('contact.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Short',
        ]);

        $response->assertSessionHasErrors(['message']);
    }

    public function test_post_contact_with_message_too_long_returns_validation_error(): void
    {
        $response = $this->post(route('contact.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => str_repeat('a', 10001),
        ]);

        $response->assertSessionHasErrors(['message']);
    }

    public function test_post_contact_stores_ip_address_and_user_agent(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Agent Test',
            'email' => 'agent@example.com',
            'message' => 'Testing IP and user agent storage.',
        ], [
            'HTTP_USER_AGENT' => 'TestBot/1.0',
        ]);

        $message = ContactMessage::where('email', 'agent@example.com')->first();
        $this->assertNotNull($message->ip_address);
        $this->assertEquals('TestBot/1.0', $message->user_agent);
    }

    public function test_post_contact_does_not_allow_status_injection(): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'message' => 'Trying to inject status field.',
            'status' => 'archived',
        ]);

        $message = ContactMessage::where('email', 'hacker@example.com')->first();
        $this->assertEquals(ContactMessageStatus::New, $message->status);
    }

    public function test_post_contact_does_not_allow_read_at_injection(): void
    {
        $now = now()->toDateTimeString();

        $this->post(route('contact.store'), [
            'name' => 'Hacker',
            'email' => 'hacker2@example.com',
            'message' => 'Trying to inject read_at field.',
            'read_at' => $now,
        ]);

        $message = ContactMessage::where('email', 'hacker2@example.com')->first();
        $this->assertNull($message->read_at);
    }

    public function test_post_contact_does_not_allow_replied_at_injection(): void
    {
        $now = now()->toDateTimeString();

        $this->post(route('contact.store'), [
            'name' => 'Hacker',
            'email' => 'hacker3@example.com',
            'message' => 'Trying to inject replied_at field.',
            'replied_at' => $now,
        ]);

        $message = ContactMessage::where('email', 'hacker3@example.com')->first();
        $this->assertNull($message->replied_at);
    }

    public function test_xss_payloads_stored_as_plain_text(): void
    {
        $xss = '<script>alert("xss")</script>';

        $this->post(route('contact.store'), [
            'name' => $xss,
            'email' => 'xss@example.com',
            'subject' => $xss,
            'message' => 'Hello, this is a normal message with '.$xss.' inside.',
        ]);

        $message = ContactMessage::where('email', 'xss@example.com')->first();
        $this->assertEquals($xss, $message->name);
        $this->assertEquals($xss, $message->subject);
        $this->assertStringContainsString($xss, $message->message);
    }

    public function test_contact_page_displays_settings_when_configured(): void
    {
        Setting::setValue('contact', 'email', 'contact@example.com');
        Setting::setValue('contact', 'phone', '555-1234');
        Setting::setValue('contact', 'address', '123 Main St');

        $response = $this->get(route('contact.show'));

        $response->assertOk();
        $response->assertViewHas('settings');
    }
}
