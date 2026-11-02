<?php

namespace Tests\Feature\Admin;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
    }

    public function test_admin_navigation_follows_required_order(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $labels = ['Dashboard', 'Orders', 'Messages', 'Products', 'Blog', 'Content', 'Configuration', 'Settings'];
        $positions = array_map(fn (string $label) => strpos($html, $label), $labels);

        foreach ($positions as $position) {
            $this->assertNotFalse($position);
        }

        for ($index = 1; $index < count($positions); $index++) {
            $this->assertGreaterThan($positions[$index - 1], $positions[$index]);
        }

        $this->assertStringContainsString('<span class="nav-link">Media</span>', $html);
        $this->assertStringNotContainsString('nav-link text-muted', $html);
    }

    public function test_messages_badge_shows_unread_count_and_updates_when_marked_read(): void
    {
        $messages = ContactMessage::factory()->count(3)->create(['status' => ContactMessageStatus::New]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $messagesPosition = strpos($html, 'Messages');
        $this->assertNotFalse($messagesPosition);
        $this->assertStringContainsString('>3</span>', substr($html, $messagesPosition, 500));

        $messages->first()->markRead();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $messagesPosition = strpos($html, 'Messages');
        $this->assertStringContainsString('>2</span>', substr($html, $messagesPosition, 500));
    }

    public function test_messages_badge_is_hidden_when_there_are_no_unread_messages(): void
    {
        ContactMessage::factory()->create(['status' => ContactMessageStatus::Read]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $messagesPosition = strpos($html, 'Messages');
        $this->assertStringNotContainsString('bg-danger ms-1', substr($html, $messagesPosition, 500));
    }
}
