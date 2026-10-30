<?php

namespace Tests\Feature\Cms;

use App\Models\Faq;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_cannot_access_admin(): void
    {
        $this->get(route('admin.faqs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_faqs_view_gets_403(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.faqs.index'))
            ->assertForbidden();
    }

    public function test_user_with_faqs_view_can_access_index(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.faqs.index'))
            ->assertOk();
    }

    public function test_index_displays_faq_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        Faq::factory()->create([
            'created_by' => $user->id,
            'question' => 'How should I store my jerky?',
        ]);

        $this->actingAs($user)->get(route('admin.faqs.index'))
            ->assertOk()
            ->assertSee('How should I store my jerky?');
    }

    public function test_user_with_faqs_create_can_access_create_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.faqs.create'))
            ->assertOk();
    }

    public function test_user_can_create_faq_with_valid_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.faqs.store'), [
            'question' => 'Do you offer gift bundles?',
            'answer' => '<p>Yes, gift bundles are available.</p>',
            'category' => 'Gifts',
        ])->assertRedirect();

        $this->assertDatabaseHas('faqs', [
            'question' => 'Do you offer gift bundles?',
            'category' => 'Gifts',
        ]);
    }

    public function test_created_by_and_updated_by_set_correctly(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.faqs.store'), [
            'question' => 'Ownership test question?',
            'answer' => '<p>Test answer</p>',
        ]);

        $faq = Faq::where('question', 'Ownership test question?')->first();
        $this->assertSame($user->id, $faq->created_by);
        $this->assertSame($user->id, $faq->updated_by);
    }

    public function test_answer_sanitized_on_store(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.faqs.store'), [
            'question' => 'Sanitize test?',
            'answer' => '<p>Safe answer</p><script>alert("xss")</script>',
        ]);

        $faq = Faq::where('question', 'Sanitize test?')->first();
        $this->assertStringNotContainsString('<script>', $faq->answer);
        $this->assertStringContainsString('<p>Safe answer</p>', $faq->answer);
    }

    public function test_category_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.faqs.store'), [
            'question' => 'Category test?',
            'answer' => '<p>Answer</p>',
            'category' => 'Pricing',
        ]);

        $faq = Faq::where('question', 'Category test?')->first();
        $this->assertSame('Pricing', $faq->category);
    }

    public function test_user_can_edit_faq(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $faq = Faq::factory()->create([
            'created_by' => $user->id,
            'question' => 'Original question?',
        ]);

        $this->actingAs($user)->put(route('admin.faqs.update', $faq), [
            'question' => 'Updated question?',
            'answer' => '<p>Updated answer</p>',
        ])->assertRedirect();

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'Updated question?',
        ]);
    }

    public function test_user_can_soft_delete_faq(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $faq = Faq::factory()->create([
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->delete(route('admin.faqs.destroy', $faq))
            ->assertRedirect(route('admin.faqs.index'));

        $this->assertSoftDeleted('faqs', ['id' => $faq->id]);
    }

    public function test_user_can_restore_faq(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $faq = Faq::factory()->create([
            'created_by' => $user->id,
        ]);
        $faq->delete();

        $this->actingAs($user)->post(route('admin.faqs.restore', $faq))
            ->assertRedirect();

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_force_delete_faq(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $faq = Faq::factory()->create([
            'created_by' => $user->id,
        ]);
        $faq->delete();

        $this->actingAs($user)->delete(route('admin.faqs.force-delete', $faq))
            ->assertRedirect(route('admin.faqs.index'));

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_active_flag_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.faqs.store'), [
            'question' => 'Inactive FAQ?',
            'answer' => '<p>Answer</p>',
            'active' => false,
        ]);

        $faq = Faq::where('question', 'Inactive FAQ?')->first();
        $this->assertFalse($faq->active);
    }

    public function test_sort_order_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->post(route('admin.faqs.store'), [
            'question' => 'Sorted FAQ?',
            'answer' => '<p>Answer</p>',
            'sort_order' => 4,
        ]);

        $faq = Faq::where('question', 'Sorted FAQ?')->first();
        $this->assertSame(4, $faq->sort_order);
    }
}
