<?php

namespace Tests\Feature\Cms;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PagePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function getPreviewUrl(Page $page): string
    {
        return URL::temporarySignedRoute(
            'pages.preview',
            now()->addMinutes(30),
            ['page' => $page->slug]
        );
    }

    public function test_signed_auth_and_authorized_user_succeeds(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->get($this->getPreviewUrl($page))
            ->assertOk();
    }

    public function test_guest_with_valid_signed_url_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $user->id]);

        $this->get($this->getPreviewUrl($page))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_unauthorized_user_gets_403(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $user->id]);

        $unauthorizedUser = User::factory()->create();

        $this->actingAs($unauthorizedUser)->get($this->getPreviewUrl($page))
            ->assertForbidden();
    }

    public function test_unsigned_url_fails(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->get(route('pages.preview', $page->slug))
            ->assertForbidden();
    }

    public function test_tampered_signature_fails(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $user->id]);

        $url = $this->getPreviewUrl($page);
        $tamperedUrl = $url.'&signature=tampered';

        $this->actingAs($user)->get($tamperedUrl)
            ->assertForbidden();
    }

    public function test_expired_signature_fails(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $user->id]);

        $expiredUrl = URL::temporarySignedRoute(
            'pages.preview',
            now()->subMinute(),
            ['page' => $page->slug]
        );

        $this->actingAs($user)->get($expiredUrl)
            ->assertForbidden();
    }

    public function test_preview_shows_noindex_nofollow(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->get($this->getPreviewUrl($page))
            ->assertOk()
            ->assertSee('noindex,nofollow');
    }

    public function test_preview_does_not_canonicalize_to_token_url(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $page = Page::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->get($this->getPreviewUrl($page))
            ->assertOk()
            ->assertDontSee('rel="canonical"', false);
    }
}
