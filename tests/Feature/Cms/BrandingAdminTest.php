<?php

namespace Tests\Feature\Cms;

use App\Models\SiteBranding;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BrandingAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function adminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        return $user;
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

    public function test_administrator_can_upload_logo(): void
    {
        $user = $this->adminUser();
        $file = UploadedFile::fake()->image('logo.jpg', 200, 60);

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => ['site_name' => 'Test'],
            'logo' => $file,
        ])->assertRedirect();

        $branding = SiteBranding::first();
        $this->assertNotNull($branding);
        $this->assertNotNull($branding->getFirstMedia('logo'));
    }

    public function test_logo_accepted_types(): void
    {
        $user = $this->adminUser();
        $file = UploadedFile::fake()->image('logo.png', 200, 60)->mimeType('image/png');

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => ['site_name' => 'Test'],
            'logo' => $file,
        ])->assertRedirect();

        $branding = SiteBranding::first();
        $this->assertNotNull($branding->getFirstMedia('logo'));
    }

    public function test_svg_logo_rejected(): void
    {
        $user = $this->adminUser();
        $file = UploadedFile::fake()->create('logo.svg', 100, 'image/svg+xml');

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => ['site_name' => 'Test'],
            'logo' => $file,
        ])->assertSessionHasErrors('logo');
    }

    public function test_non_image_rejected(): void
    {
        $user = $this->adminUser();
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => ['site_name' => 'Test'],
            'logo' => $file,
        ])->assertSessionHasErrors('logo');
    }

    public function test_logo_alt_text_persists(): void
    {
        $user = $this->adminUser();
        $file = UploadedFile::fake()->image('logo.jpg', 200, 60);

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => ['site_name' => 'Test'],
            'logo' => $file,
            'logo_alt_text' => 'My Brand Logo',
        ])->assertRedirect();

        $branding = SiteBranding::first();
        $media = $branding->getFirstMedia('logo');
        $this->assertNotNull($media);
    }

    public function test_logo_replacement_works(): void
    {
        $user = $this->adminUser();

        $branding = SiteBranding::firstOrCreate([], []);
        $file1 = UploadedFile::fake()->image('old-logo.jpg', 200, 60);
        $branding->addMedia($file1)->toMediaCollection('logo');

        $file2 = UploadedFile::fake()->image('new-logo.jpg', 200, 60);

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => ['site_name' => 'Test'],
            'logo' => $file2,
        ])->assertRedirect();

        $branding->refresh();
        $media = $branding->getFirstMedia('logo');
        $this->assertNotNull($media);
        $this->assertSame('new-logo.jpg', $media->file_name);
    }

    public function test_edit_without_upload_preserves(): void
    {
        $user = $this->adminUser();

        $branding = SiteBranding::firstOrCreate([], []);
        $file = UploadedFile::fake()->image('existing-logo.jpg', 200, 60);
        $branding->addMedia($file)->toMediaCollection('logo');

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => ['site_name' => 'Updated Name'],
        ])->assertRedirect();

        $branding->refresh();
        $this->assertNotNull($branding->getFirstMedia('logo'));
    }

    public function test_explicit_removal_works(): void
    {
        $user = $this->adminUser();

        $branding = SiteBranding::firstOrCreate([], []);
        $file = UploadedFile::fake()->image('logo.jpg', 200, 60);
        $branding->addMedia($file)->toMediaCollection('logo');
        $this->assertNotNull($branding->getFirstMedia('logo'));

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => ['site_name' => 'Test'],
            'remove_logo' => true,
        ])->assertRedirect();

        $branding->refresh();
        $this->assertNull($branding->getFirstMedia('logo'));
    }

    public function test_favicon_upload_works(): void
    {
        $user = $this->adminUser();
        $file = UploadedFile::fake()->image('favicon.png', 32, 32);

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => ['site_name' => 'Test'],
            'favicon' => $file,
        ])->assertRedirect();

        $branding = SiteBranding::first();
        $this->assertNotNull($branding);
        $this->assertNotNull($branding->getFirstMedia('favicon'));
    }

    public function test_social_image_upload_works(): void
    {
        $user = $this->adminUser();
        $file = UploadedFile::fake()->image('social.jpg', 1200, 630);

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => ['site_name' => 'Test'],
            'social_image' => $file,
        ])->assertRedirect();
    }

    public function test_public_logo_renders(): void
    {
        $branding = SiteBranding::firstOrCreate([], []);
        $file = UploadedFile::fake()->image('logo.jpg', 200, 60);
        $branding->addMedia($file)->toMediaCollection('logo');

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('logo.jpg');
    }

    public function test_public_favicon_renders(): void
    {
        $branding = SiteBranding::firstOrCreate([], []);
        $file = UploadedFile::fake()->image('favicon.png', 32, 32);
        $branding->addMedia($file)->toMediaCollection('favicon');

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('favicon.png');
    }
}
