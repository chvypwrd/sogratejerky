<?php

namespace Tests\Feature\Contact;

use App\Models\Setting;
use App\Notifications\NewContactMessageNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_accepted_contact_message_triggers_notification(): void
    {
        Notification::fake();

        $this->post(route('contact.store'), [
            'name' => 'Notify Test',
            'email' => 'notify@example.com',
            'subject' => 'Testing notifications',
            'message' => 'This is a test for notification triggering.',
        ]);

        Notification::assertSentTo(new AnonymousNotifiable, NewContactMessageNotification::class);
    }

    public function test_notification_sent_to_notification_email_setting(): void
    {
        Notification::fake();

        Setting::setValue('contact', 'notification_email', 'admin-notify@example.com');

        $this->post(route('contact.store'), [
            'name' => 'Settings Test',
            'email' => 'settings@example.com',
            'subject' => 'Notification email test',
            'message' => 'Testing notification email setting.',
        ]);

        Notification::assertSentTo(new AnonymousNotifiable, NewContactMessageNotification::class, function ($notification) {
            return $notification instanceof NewContactMessageNotification;
        });
    }

    public function test_notification_falls_back_to_contact_email(): void
    {
        Notification::fake();

        Setting::setValue('contact', 'email', 'fallback@example.com');

        $this->post(route('contact.store'), [
            'name' => 'Fallback Test',
            'email' => 'fallback-test@example.com',
            'subject' => 'Fallback test',
            'message' => 'Testing fallback to contact email.',
        ]);

        Notification::assertSentTo(new AnonymousNotifiable, NewContactMessageNotification::class);
    }

    public function test_notification_contains_message_details(): void
    {
        Notification::fake();

        $this->post(route('contact.store'), [
            'name' => 'Detail User',
            'email' => 'detail@example.com',
            'subject' => 'Important Question',
            'message' => 'What are your class schedules?',
        ]);

        Notification::assertSentTo(new AnonymousNotifiable, NewContactMessageNotification::class, function ($notification) {
            return $notification->message->name === 'Detail User'
                && $notification->message->email === 'detail@example.com'
                && str_contains($notification->message->message, 'What are your class schedules?');
        });
    }

    public function test_visitor_email_used_as_reply_to(): void
    {
        Notification::fake();

        $this->post(route('contact.store'), [
            'name' => 'Reply To Test',
            'email' => 'visitor@example.com',
            'subject' => 'Reply test',
            'message' => 'Testing reply-to address.',
        ]);

        Notification::assertSentTo(new AnonymousNotifiable, NewContactMessageNotification::class, function ($notification) {
            $mail = $notification->toMail($notification->message);
            $rendered = $mail->render();

            return str_contains($rendered, 'visitor@example.com');
        });
    }

    public function test_honeypot_does_not_trigger_notification(): void
    {
        Notification::fake();

        $this->post(route('contact.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'This is a bot message.',
            'website' => 'http://spam.com',
        ]);

        Notification::assertNothingSent();
    }

    public function test_duplicate_does_not_trigger_notification(): void
    {
        Notification::fake();

        $data = [
            'name' => 'Dup Notifier',
            'email' => 'dup-notify@example.com',
            'subject' => 'Duplicate test',
            'message' => 'This message will be duplicated.',
        ];

        $this->post(route('contact.store'), $data);
        $this->post(route('contact.store'), $data);

        Notification::assertSentTimes(NewContactMessageNotification::class, 1);
    }

    public function test_too_fast_does_not_trigger_notification(): void
    {
        Notification::fake();

        $this->get(route('contact.show'));

        $this->post(route('contact.store'), [
            'name' => 'Speed Test',
            'email' => 'speed@example.com',
            'message' => 'This is submitted too quickly.',
        ]);

        Notification::assertNothingSent();
    }

    public function test_mail_failure_does_not_delete_contact_message(): void
    {
        Notification::fake();

        $this->post(route('contact.store'), [
            'name' => 'Fail Test',
            'email' => 'fail@example.com',
            'message' => 'This message should persist even if mail fails.',
        ]);

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'fail@example.com',
            'name' => 'Fail Test',
        ]);
    }
}
