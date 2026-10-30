<?php

namespace Tests\Feature\Cms;

use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_blocked(): void
    {
        $this->get(route('admin.redirects.index'))
            ->assertRedirect(route('login'));
    }

    public function test_editor_blocked(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.redirects.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_view_redirects(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->get(route('admin.redirects.index'))
            ->assertOk();
    }

    public function test_can_create_301_redirect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.redirects.store'), [
            'old_path' => '/old-page',
            'new_path' => '/new-page',
            'status_code' => 301,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/old-page',
            'new_path' => '/new-page',
            'status_code' => 301,
            'active' => true,
        ]);
    }

    public function test_can_create_302_redirect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.redirects.store'), [
            'old_path' => '/temp-page',
            'new_path' => '/new-temp',
            'status_code' => 302,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/temp-page',
            'new_path' => '/new-temp',
            'status_code' => 302,
        ]);
    }

    public function test_can_edit_redirect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $redirect = Redirect::create([
            'old_path' => '/original',
            'new_path' => '/destination',
            'status_code' => 301,
            'active' => true,
        ]);

        $this->actingAs($user)->put(route('admin.redirects.update', $redirect), [
            'old_path' => '/original',
            'new_path' => '/updated-destination',
            'status_code' => 302,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('redirects', [
            'id' => $redirect->id,
            'new_path' => '/updated-destination',
            'status_code' => 302,
        ]);
    }

    public function test_can_delete_redirect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $redirect = Redirect::create([
            'old_path' => '/to-delete',
            'new_path' => '/somewhere',
            'status_code' => 301,
            'active' => true,
        ]);

        $this->actingAs($user)->delete(route('admin.redirects.destroy', $redirect))
            ->assertRedirect();

        $this->assertDatabaseMissing('redirects', ['id' => $redirect->id]);
    }

    public function test_self_redirect_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.redirects.store'), [
            'old_path' => '/same-page',
            'new_path' => '/same-page',
            'status_code' => 301,
            'active' => true,
        ])->assertSessionHasErrors('new_path');
    }

    public function test_external_target_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.redirects.store'), [
            'old_path' => '/old-page',
            'new_path' => 'https://external.com',
            'status_code' => 301,
            'active' => true,
        ])->assertSessionHasErrors('new_path');
    }

    public function test_loop_detection(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        Redirect::create([
            'old_path' => '/page-a',
            'new_path' => '/page-b',
            'status_code' => 301,
            'active' => true,
        ]);

        $this->actingAs($user)->post(route('admin.redirects.store'), [
            'old_path' => '/page-b',
            'new_path' => '/page-a',
            'status_code' => 301,
            'active' => true,
        ])->assertSessionHasErrors('new_path');
    }

    public function test_deactivated_redirect_not_resolved(): void
    {
        $redirect = Redirect::create([
            'old_path' => '/inactive',
            'new_path' => '/active',
            'status_code' => 301,
            'active' => false,
        ]);

        $found = Redirect::findForPath('/inactive');
        $this->assertNull($found);
    }

    public function test_auto_generated_slug_redirects_appear_in_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        Redirect::create([
            'old_path' => '/old-slug',
            'new_path' => '/new-slug',
            'status_code' => 301,
            'active' => true,
        ]);

        $this->actingAs($user)->get(route('admin.redirects.index'))
            ->assertOk()
            ->assertSee('old-slug')
            ->assertSee('new-slug');
    }
}
