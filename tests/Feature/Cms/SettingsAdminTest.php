<?php

namespace Tests\Feature\Cms;

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    protected function validSettings(array $overrides = []): array
    {
        return array_merge([
            'settings' => [
                'site_name' => 'SOGRATE',
                'tagline' => 'Texas Made. Seriously Good Jerky.',
            ],
        ], $overrides);
    }

    public function test_guest_blocked(): void
    {
        $this->get(route('admin.settings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_editor_blocked(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_administrator_allowed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_super_admin_allowed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_can_update_site_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->put(route('admin.settings.update'), $this->validSettings([
            'settings' => ['site_name' => 'New Site Name'],
        ]))->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'site_name',
            'value' => 'New Site Name',
        ]);
    }

    public function test_can_update_tagline(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->put(route('admin.settings.update'), $this->validSettings([
            'settings' => ['site_name' => 'Test', 'tagline' => 'New Tagline Here'],
        ]))->assertRedirect();

        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'tagline',
            'value' => 'New Tagline Here',
        ]);
    }

    public function test_can_update_contact_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->put(route('admin.settings.update'), $this->validSettings([
            'settings' => [
                'site_name' => 'Test',
                'email' => 'test@example.com',
                'phone' => '555-1234',
                'address' => '123 Main St',
            ],
        ]))->assertRedirect();

        $this->assertDatabaseHas('settings', ['group' => 'contact', 'key' => 'email', 'value' => 'test@example.com']);
        $this->assertDatabaseHas('settings', ['group' => 'contact', 'key' => 'phone', 'value' => '555-1234']);
        $this->assertDatabaseHas('settings', ['group' => 'contact', 'key' => 'address', 'value' => '123 Main St']);
    }

    public function test_can_update_social_urls(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->put(route('admin.settings.update'), $this->validSettings([
            'settings' => [
                'site_name' => 'Test',
                'facebook' => 'https://facebook.com/test',
                'instagram' => 'https://instagram.com/test',
                'youtube' => 'https://youtube.com/test',
            ],
        ]))->assertRedirect();

        $this->assertDatabaseHas('settings', ['group' => 'social', 'key' => 'facebook', 'value' => 'https://facebook.com/test']);
        $this->assertDatabaseHas('settings', ['group' => 'social', 'key' => 'instagram', 'value' => 'https://instagram.com/test']);
        $this->assertDatabaseHas('settings', ['group' => 'social', 'key' => 'youtube', 'value' => 'https://youtube.com/test']);
    }

    public function test_unsafe_url_scheme_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->put(route('admin.settings.update'), $this->validSettings([
            'settings' => ['site_name' => 'Test', 'facebook' => 'javascript:alert(1)'],
        ]))->assertSessionHasErrors('settings.facebook');
    }

    public function test_unknown_setting_key_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $service = app(SettingsService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->set('nonexistent.key', 'value');
    }

    public function test_values_persist(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->put(route('admin.settings.update'), $this->validSettings([
            'settings' => ['site_name' => 'Persistent Name'],
        ]))->assertRedirect();

        Cache::flush();

        $service = app(SettingsService::class);
        $this->assertSame('Persistent Name', $service->get('general.site_name'));
    }

    public function test_cache_invalidates_after_update(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $service = app(SettingsService::class);

        $initial = $service->get('general.site_name');
        $this->assertNotNull(Cache::get('settings.all'));

        $this->actingAs($user)->put(route('admin.settings.update'), $this->validSettings([
            'settings' => ['site_name' => 'Updated Name'],
        ]))->assertRedirect();

        $this->assertNull(Cache::get('settings.all'));

        Cache::flush();
        $updated = $service->get('general.site_name');
        $this->assertEquals('Updated Name', $updated);
    }

    public function test_seeder_idempotent(): void
    {
        Setting::where('group', 'general')->where('key', 'site_name')->update(['value' => 'Admin Custom Name']);

        $this->seed(SettingsSeeder::class);

        $this->assertDatabaseHas('settings', [
            'group' => 'general',
            'key' => 'site_name',
            'value' => 'Admin Custom Name',
        ]);
    }

    public function test_settings_output_escaped(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->put(route('admin.settings.update'), $this->validSettings([
            'settings' => ['site_name' => '<script>alert("xss")</script>'],
        ]));

        $response = $this->actingAs($user)->get(route('admin.settings.index'));
        $response->assertOk();
        $response->assertDontSee('<script>alert("xss")</script>', false);
    }
}
