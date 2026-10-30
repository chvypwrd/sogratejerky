<?php

namespace Tests\Feature\Contact;

use App\Models\ContactMessage;
use App\Services\ContactSpamGuard;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactSpamGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_honeypot_empty_passes(): void
    {
        $guard = new ContactSpamGuard;
        $request = Request::create('/contact', 'POST', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'message' => 'This is a valid message.',
        ]);

        $this->assertFalse($guard->checkHoneypot($request));
    }

    public function test_honeypot_filled_rejected_no_db_record_no_notification(): void
    {
        Notification::fake();

        $this->post(route('contact.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'Spam message here.',
            'website' => 'http://spam-site.com',
        ]);

        $this->assertDatabaseMissing('contact_messages', [
            'email' => 'bot@example.com',
        ]);

        Notification::assertNothingSent();
    }

    public function test_honeypot_rejection_returns_success_like_response(): void
    {
        $response = $this->post(route('contact.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'Spam message here.',
            'website' => 'http://spam-site.com',
        ]);

        $response->assertRedirect(route('contact.show'));
    }

    public function test_minimum_time_form_loaded_then_immediate_submit_rejected(): void
    {
        $guard = new ContactSpamGuard;

        $request = Request::create('/contact', 'POST');
        $session = $this->app['session.store'];
        $session->put('contact_form_start', now()->toDateTimeString());
        $request->setLaravelSession($session);

        $this->assertTrue($guard->checkMinimumTime($request));
    }

    public function test_minimum_time_form_loaded_with_delay_accepted(): void
    {
        $guard = new ContactSpamGuard;

        $request = Request::create('/contact', 'POST');
        $session = $this->app['session.store'];
        $session->put('contact_form_start', now()->subSeconds(3)->toDateTimeString());
        $request->setLaravelSession($session);

        $this->assertFalse($guard->checkMinimumTime($request));
    }

    public function test_duplicate_same_content_within五分钟_rejected(): void
    {
        $guard = new ContactSpamGuard;

        $email = 'dup@example.com';
        $subject = 'Test Subject';
        $message = 'This is a duplicate message.';

        $guard->recordFingerprint($email, $subject, $message);

        $this->assertTrue($guard->checkDuplicate($email, $subject, $message));
    }

    public function test_duplicate_same_content_after五分钟_accepted(): void
    {
        $guard = new ContactSpamGuard;

        $email = 'dup2@example.com';
        $subject = 'Test Subject';
        $message = 'This message will expire.';

        $guard->recordFingerprint($email, $subject, $message);

        Cache::flush();

        $this->assertFalse($guard->checkDuplicate($email, $subject, $message));
    }

    public function test_duplicate_different_message_accepted(): void
    {
        $guard = new ContactSpamGuard;

        $email = 'diff@example.com';
        $subject = 'Test Subject';
        $message1 = 'First message content.';
        $message2 = 'Second message content.';

        $guard->recordFingerprint($email, $subject, $message1);

        $this->assertFalse($guard->checkDuplicate($email, $subject, $message2));
    }

    public function test_rate_limit_five_submissions_from_same_ip_succeed(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('contact.store'), [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'message' => "This is message number {$i} for rate limit testing.",
            ]);

            $response->assertRedirect(route('contact.show'));
        }

        $this->assertEquals(5, ContactMessage::count());
    }

    public function test_rate_limit_sixth_submission_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.store'), [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'message' => "This is message number {$i} for rate limit testing.",
            ]);
        }

        $response = $this->post(route('contact.store'), [
            'name' => 'User 5',
            'email' => 'user5@example.com',
            'message' => 'This is the sixth message that should be throttled.',
        ]);

        $response->assertStatus(429);
    }

    public function test_rate_limit_different_ip_gets_independent_allowance(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.store'), [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'message' => "Message from first IP number {$i}.",
            ], [
                'REMOTE_ADDR' => '192.168.1.1',
            ]);
        }

        $response = $this->post(route('contact.store'), [
            'name' => 'New IP User',
            'email' => 'newip@example.com',
            'message' => 'Message from a different IP address.',
        ], [
            'REMOTE_ADDR' => '10.0.0.1',
        ]);

        $response->assertRedirect(route('contact.show'));
    }
}
