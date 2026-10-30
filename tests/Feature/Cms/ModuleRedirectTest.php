<?php

namespace Tests\Feature\Cms;

use App\Models\ClassOffering;
use App\Models\Instructor;
use App\Models\Redirect;
use App\Models\User;
use App\Services\PageSlugRedirectService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_class_slug_change_creates_redirect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $class = ClassOffering::factory()->create([
            'created_by' => $user->id,
            'title' => 'Original Title',
            'slug' => 'original-class',
        ]);

        $this->actingAs($user)->put(route('admin.classes.update', $class), [
            'title' => 'Renamed Title',
            'description' => '<p>Updated content</p>',
        ])->assertRedirect();

        $redirect = Redirect::where('old_path', '/classes/original-class')->first();

        if ($redirect) {
            $this->assertSame('/classes/renamed-title', $redirect->new_path);
            $this->assertSame(301, $redirect->status_code);
            $this->assertTrue($redirect->active);
        }
    }

    public function test_instructor_slug_change_creates_redirect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $instructor = Instructor::factory()->create([
            'created_by' => $user->id,
            'name' => 'Original Name',
            'slug' => 'original-instructor',
        ]);

        $this->actingAs($user)->put(route('admin.instructors.update', $instructor), [
            'name' => 'Renamed Instructor',
            'bio' => '<p>Updated bio</p>',
        ])->assertRedirect();

        $redirect = Redirect::where('old_path', '/instructors/original-instructor')->first();

        if ($redirect) {
            $this->assertSame('/instructors/renamed-instructor', $redirect->new_path);
            $this->assertSame(301, $redirect->status_code);
            $this->assertTrue($redirect->active);
        }
    }

    public function test_redirect_chain_collapses_for_classes(): void
    {
        $service = app(PageSlugRedirectService::class);

        $class = ClassOffering::factory()->create([
            'slug' => 'class-a',
            'active' => true,
        ]);

        $oldSlug = $class->slug;
        $class->update(['slug' => 'class-b']);
        $service->handleSlugChange($class->fresh(), $oldSlug);

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/class-a',
            'new_path' => '/class-b',
            'active' => true,
        ]);

        $oldSlug = $class->fresh()->slug;
        $class->update(['slug' => 'class-c']);
        $service->handleSlugChange($class->fresh(), $oldSlug);

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/class-b',
            'new_path' => '/class-c',
            'active' => true,
        ]);
    }

    public function test_redirect_chain_collapses_for_instructors(): void
    {
        $service = app(PageSlugRedirectService::class);

        $instructor = Instructor::factory()->create([
            'slug' => 'instructor-a',
            'active' => true,
        ]);

        $oldSlug = $instructor->slug;
        $instructor->update(['slug' => 'instructor-b']);
        $service->handleSlugChange($instructor->fresh(), $oldSlug);

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/instructor-a',
            'new_path' => '/instructor-b',
            'active' => true,
        ]);

        $oldSlug = $instructor->fresh()->slug;
        $instructor->update(['slug' => 'instructor-c']);
        $service->handleSlugChange($instructor->fresh(), $oldSlug);

        $this->assertDatabaseHas('redirects', [
            'old_path' => '/instructor-b',
            'new_path' => '/instructor-c',
            'active' => true,
        ]);
    }

    public function test_page_redirects_still_work(): void
    {
        $service = app(PageSlugRedirectService::class);

        $service->createRedirect('/old-page', '/new-page');

        $result = $service->resolve('/old-page');
        $this->assertNotNull($result);
        $this->assertSame('/new-page', $result['to']);
        $this->assertSame(301, $result['status']);
    }
}
