<?php

namespace Tests\Feature\Cms;

use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_admin(): void
    {
        $this->get(route('admin.testimonials.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_testimonials_view_gets_403(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.testimonials.index'))
            ->assertForbidden();
    }

    public function test_user_with_testimonials_view_can_access_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.testimonials.index'))
            ->assertOk();
    }

    public function test_index_displays_testimonial_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        Testimonial::factory()->create([
            'created_by' => $user->id,
            'name' => 'Jane Doe',
        ]);

        $this->actingAs($user)->get(route('admin.testimonials.index'))
            ->assertOk()
            ->assertSee('Jane Doe');
    }

    public function test_user_with_testimonials_create_can_access_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.testimonials.create'))
            ->assertOk();
    }

    public function test_user_can_create_testimonial_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.testimonials.store'), [
            'name' => 'John Smith',
            'quote' => 'Great kickboxing program!',
            'attribution' => 'Parent',
        ])->assertRedirect();

        $this->assertDatabaseHas('testimonials', [
            'name' => 'John Smith',
            'quote' => 'Great kickboxing program!',
        ]);
    }

    public function test_created_by_and_updated_by_set_correctly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.testimonials.store'), [
            'name' => 'Ownership Test',
            'quote' => 'Test quote',
        ]);

        $testimonial = Testimonial::where('name', 'Ownership Test')->first();
        $this->assertSame($user->id, $testimonial->created_by);
        $this->assertSame($user->id, $testimonial->updated_by);
    }

    public function test_user_can_edit_testimonial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $testimonial = Testimonial::factory()->create([
            'created_by' => $user->id,
            'name' => 'Original Name',
        ]);

        $this->actingAs($user)->put(route('admin.testimonials.update', $testimonial), [
            'name' => 'Updated Name',
            'quote' => 'Updated quote',
        ])->assertRedirect();

        $this->assertDatabaseHas('testimonials', [
            'id' => $testimonial->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_soft_delete_testimonial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $testimonial = Testimonial::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->delete(route('admin.testimonials.destroy', $testimonial))
            ->assertRedirect(route('admin.testimonials.index'));

        $this->assertSoftDeleted('testimonials', ['id' => $testimonial->id]);
    }

    public function test_user_can_restore_testimonial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $testimonial = Testimonial::factory()->create([
            'created_by' => $user->id,
        ]);
        $testimonial->delete();

        $this->actingAs($user)->post(route('admin.testimonials.restore', $testimonial))
            ->assertRedirect();

        $this->assertDatabaseHas('testimonials', [
            'id' => $testimonial->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_force_delete_testimonial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $testimonial = Testimonial::factory()->create([
            'created_by' => $user->id,
        ]);
        $testimonial->delete();

        $this->actingAs($user)->delete(route('admin.testimonials.force-delete', $testimonial))
            ->assertRedirect(route('admin.testimonials.index'));

        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }

    public function test_editor_cannot_delete(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        $testimonial = Testimonial::factory()->create(['created_by' => $editor->id]);

        $this->actingAs($editor)->delete(route('admin.testimonials.destroy', $testimonial))
            ->assertForbidden();
    }

    public function test_quote_escaped_no_raw_html(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $testimonial = Testimonial::factory()->create([
            'created_by' => $user->id,
            'quote' => 'Great program! <script>alert("xss")</script>',
        ]);

        $this->actingAs($user)->get(route('admin.testimonials.index'))
            ->assertOk()
            ->assertSee(e('Great program! <script>alert("xss")</script>'), false)
            ->assertDontSee('<script>', false);
    }

    public function test_featured_flag_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.testimonials.store'), [
            'name' => 'Featured Person',
            'quote' => 'Featured quote',
            'featured' => true,
        ]);

        $testimonial = Testimonial::where('name', 'Featured Person')->first();
        $this->assertTrue($testimonial->featured);
    }

    public function test_active_flag_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.testimonials.store'), [
            'name' => 'Inactive Person',
            'quote' => 'Inactive quote',
            'active' => false,
        ]);

        $testimonial = Testimonial::where('name', 'Inactive Person')->first();
        $this->assertFalse($testimonial->active);
    }

    public function test_sort_order_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.testimonials.store'), [
            'name' => 'Sorted Person',
            'quote' => 'Sorted quote',
            'sort_order' => 7,
        ]);

        $testimonial = Testimonial::where('name', 'Sorted Person')->first();
        $this->assertSame(7, $testimonial->sort_order);
    }
}
