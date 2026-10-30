<?php

namespace Tests\Feature\Contact;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrator');

        $this->editor = User::factory()->create();
        $this->editor->assignRole('Editor');
    }

    public function test_guest_cannot_access_admin_contact_messages(): void
    {
        $response = $this->get(route('admin.contact-messages.index'));

        $response->assertRedirect();
    }

    public function test_editor_cannot_access_admin_contact_messages(): void
    {
        $response = $this->actingAs($this->editor)->get(route('admin.contact-messages.index'));

        $response->assertForbidden();
    }

    public function test_administrator_can_view_index(): void
    {
        ContactMessage::factory()->count(3)->create(['status' => ContactMessageStatus::New]);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index'));

        $response->assertOk();
    }

    public function test_administrator_can_view_detail(): void
    {
        $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::New]);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.show', $message));

        $response->assertOk();
    }

    public function test_viewing_new_message_marks_it_read(): void
    {
        $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::New]);

        $this->actingAs($this->admin)->get(route('admin.contact-messages.show', $message));

        $message->refresh();
        $this->assertEquals(ContactMessageStatus::Read, $message->status);
        $this->assertNotNull($message->read_at);
    }

    public function test_viewing_read_message_does_not_downgrade_status(): void
    {
        $message = ContactMessage::factory()->replied()->create();

        $this->actingAs($this->admin)->get(route('admin.contact-messages.show', $message));

        $message->refresh();
        $this->assertEquals(ContactMessageStatus::Replied, $message->status);
    }

    public function test_mark_read_action_works(): void
    {
        $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::New]);

        $this->actingAs($this->admin)->post(route('admin.contact-messages.mark-read', $message));

        $message->refresh();
        $this->assertEquals(ContactMessageStatus::Read, $message->status);
        $this->assertNotNull($message->read_at);
    }

    public function test_mark_unread_action_works(): void
    {
        $message = ContactMessage::factory()->read()->create();

        $this->actingAs($this->admin)->post(route('admin.contact-messages.mark-unread', $message));

        $message->refresh();
        $this->assertEquals(ContactMessageStatus::New, $message->status);
        $this->assertNull($message->read_at);
    }

    public function test_mark_replied_action_works(): void
    {
        $message = ContactMessage::factory()->read()->create();

        $this->actingAs($this->admin)->post(route('admin.contact-messages.mark-replied', $message));

        $message->refresh();
        $this->assertEquals(ContactMessageStatus::Replied, $message->status);
        $this->assertNotNull($message->replied_at);
    }

    public function test_archive_action_works(): void
    {
        $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::New]);

        $this->actingAs($this->admin)->post(route('admin.contact-messages.archive', $message));

        $message->refresh();
        $this->assertEquals(ContactMessageStatus::Archived, $message->status);
    }

    public function test_spam_action_works(): void
    {
        $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::New]);

        $this->actingAs($this->admin)->post(route('admin.contact-messages.spam', $message));

        $message->refresh();
        $this->assertEquals(ContactMessageStatus::Spam, $message->status);
    }

    public function test_restore_to_inbox_action_works(): void
    {
        $message = ContactMessage::factory()->spam()->create();

        $this->actingAs($this->admin)->post(route('admin.contact-messages.restore', $message));

        $message->refresh();
        $this->assertEquals(ContactMessageStatus::New, $message->status);
        $this->assertNull($message->read_at);
        $this->assertNull($message->replied_at);
    }

    public function test_delete_action_works(): void
    {
        $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::New]);

        $this->actingAs($this->admin)->delete(route('admin.contact-messages.destroy', $message));

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    }

    public function test_editor_cannot_delete(): void
    {
        $message = ContactMessage::factory()->create(['status' => ContactMessageStatus::New]);

        $response = $this->actingAs($this->editor)->delete(route('admin.contact-messages.destroy', $message));

        $response->assertForbidden();
        $this->assertDatabaseHas('contact_messages', ['id' => $message->id]);
    }

    public function test_index_filter_inbox(): void
    {
        ContactMessage::factory()->create(['status' => ContactMessageStatus::New, 'subject' => 'Inbox New']);
        ContactMessage::factory()->read()->create(['subject' => 'Inbox Read']);
        ContactMessage::factory()->replied()->create(['subject' => 'Inbox Replied']);
        $archived = ContactMessage::factory()->archived()->create(['subject' => 'Archived Subject']);
        $spam = ContactMessage::factory()->spam()->create(['subject' => 'Spam Subject']);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['filter' => 'inbox']));

        $response->assertOk();
        $response->assertSee('Inbox New');
        $response->assertSee('Inbox Read');
        $response->assertSee('Inbox Replied');
        $response->assertDontSee('Archived Subject');
        $response->assertDontSee('Spam Subject');
    }

    public function test_index_filter_new(): void
    {
        ContactMessage::factory()->create(['status' => ContactMessageStatus::New, 'subject' => 'New Msg']);
        ContactMessage::factory()->read()->create(['subject' => 'Read Msg']);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['filter' => 'new']));

        $response->assertOk();
        $response->assertSee('New Msg');
        $response->assertDontSee('Read Msg');
    }

    public function test_index_filter_read(): void
    {
        ContactMessage::factory()->create(['status' => ContactMessageStatus::New, 'subject' => 'New Msg']);
        ContactMessage::factory()->read()->create(['subject' => 'Read Msg']);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['filter' => 'read']));

        $response->assertOk();
        $response->assertSee('Read Msg');
        $response->assertDontSee('New Msg');
    }

    public function test_index_filter_replied(): void
    {
        ContactMessage::factory()->replied()->create(['subject' => 'Replied Msg']);
        ContactMessage::factory()->create(['status' => ContactMessageStatus::New, 'subject' => 'New Msg']);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['filter' => 'replied']));

        $response->assertOk();
        $response->assertSee('Replied Msg');
        $response->assertDontSee('New Msg');
    }

    public function test_index_filter_archived(): void
    {
        ContactMessage::factory()->archived()->create(['subject' => 'Archived Msg']);
        ContactMessage::factory()->create(['status' => ContactMessageStatus::New, 'subject' => 'New Msg']);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['filter' => 'archived']));

        $response->assertOk();
        $response->assertSee('Archived Msg');
        $response->assertDontSee('New Msg');
    }

    public function test_index_filter_spam(): void
    {
        ContactMessage::factory()->spam()->create(['subject' => 'Spam Msg']);
        ContactMessage::factory()->create(['status' => ContactMessageStatus::New, 'subject' => 'New Msg']);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['filter' => 'spam']));

        $response->assertOk();
        $response->assertSee('Spam Msg');
        $response->assertDontSee('New Msg');
    }

    public function test_index_filter_all(): void
    {
        ContactMessage::factory()->create(['status' => ContactMessageStatus::New, 'subject' => 'All New']);
        ContactMessage::factory()->archived()->create(['subject' => 'All Archived']);
        ContactMessage::factory()->spam()->create(['subject' => 'All Spam']);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['filter' => 'all']));

        $response->assertOk();
        $response->assertSee('All New');
        $response->assertSee('All Archived');
        $response->assertSee('All Spam');
    }

    public function test_search_by_name_works(): void
    {
        ContactMessage::factory()->create(['name' => 'Alice Johnson']);
        ContactMessage::factory()->create(['name' => 'Bob Smith']);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['search' => 'Alice']));

        $response->assertOk();
        $response->assertSee('Alice Johnson');
        $response->assertDontSee('Bob Smith');
    }

    public function test_search_by_email_works(): void
    {
        ContactMessage::factory()->create(['email' => 'alice@example.com']);
        ContactMessage::factory()->create(['email' => 'bob@example.com']);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['search' => 'alice@']));

        $response->assertOk();
        $response->assertSee('alice@example.com');
        $response->assertDontSee('bob@example.com');
    }

    public function test_search_by_subject_works(): void
    {
        ContactMessage::factory()->create(['subject' => 'Pricing Inquiry']);
        ContactMessage::factory()->create(['subject' => 'Schedule Question']);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['search' => 'Pricing']));

        $response->assertOk();
        $response->assertSee('Pricing Inquiry');
        $response->assertDontSee('Schedule Question');
    }

    public function test_pagination_works(): void
    {
        ContactMessage::factory()->count(25)->create(['status' => ContactMessageStatus::New]);

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index'));

        $response->assertOk();
        $this->assertCount(20, $response->viewData('messages')->items());
    }

    public function test_status_badges_render_correctly(): void
    {
        $new = ContactMessage::factory()->create(['status' => ContactMessageStatus::New]);
        $read = ContactMessage::factory()->read()->create();
        $replied = ContactMessage::factory()->replied()->create();
        $archived = ContactMessage::factory()->archived()->create();
        $spam = ContactMessage::factory()->spam()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.contact-messages.index', ['filter' => 'all']));

        $response->assertSee('bg-primary');
        $response->assertSee('bg-info');
        $response->assertSee('bg-success');
        $response->assertSee('bg-secondary');
        $response->assertSee('bg-danger');
    }
}
