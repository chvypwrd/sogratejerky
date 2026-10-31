<?php

namespace Tests\Feature\Contact;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_contact_does_not_resolve_as_cms_page(): void
    {
        Page::factory()->published()->create(['slug' => 'contact']);

        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertViewIs('contact.index');
    }

    public function test_about_still_resolves_as_cms_page(): void
    {
        Page::factory()->published()->create(['slug' => 'about']);

        $response = $this->get('/about');

        $response->assertOk();
        $response->assertViewIs('pages.default');
    }

    public function test_post_contact_exists_and_accepts_post(): void
    {
        $response = $this->postJson(route('contact.store'), [
            'name' => 'Route Test',
            'email' => 'route@example.com',
            'message' => 'Testing that POST /contact route exists.',
        ]);

        $response->assertRedirect();
    }

    public function test_admin_contact_messages_works_with_auth_and_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $response = $this->actingAs($user)->get(route('admin.contact-messages.index'));

        $response->assertOk();
    }

    public function test_customer_registration_route_is_available(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
    }

    public function test_all_existing_public_routes_unaffected(): void
    {
        $this->get('/')->assertOk();
        $this->get('/faq')->assertOk();
        $this->get('/contact')->assertOk();
    }
}
