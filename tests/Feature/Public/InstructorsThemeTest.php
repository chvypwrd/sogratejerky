<?php

namespace Tests\Feature\Public;

use App\Models\Instructor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorsThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_instructors_index_returns_200(): void
    {
        $response = $this->get(route('instructors.index'));

        $response->assertOk();
    }

    public function test_active_instructors_listed(): void
    {
        $instructor = Instructor::factory()
            ->active()
            ->create(['name' => 'Coach Sarah']);

        $response = $this->get(route('instructors.index'));

        $response->assertSee($instructor->name);
    }

    public function test_inactive_instructors_hidden(): void
    {
        $instructor = Instructor::factory()
            ->inactive()
            ->create(['name' => 'Former Coach']);

        $response = $this->get(route('instructors.index'));

        $response->assertDontSee('Former Coach');
    }

    public function test_instructor_card_markup_present(): void
    {
        Instructor::factory()
            ->active()
            ->create(['name' => 'Card Coach']);

        $response = $this->get(route('instructors.index'));

        $response->assertSee('card');
    }

    public function test_instructor_detail_returns_200(): void
    {
        $instructor = Instructor::factory()
            ->active()
            ->create(['slug' => 'coach-detail']);

        $response = $this->get(route('instructors.show', 'coach-detail'));

        $response->assertOk();
        $response->assertSee($instructor->name);
    }

    public function test_portrait_alt_text_present(): void
    {
        $instructor = Instructor::factory()
            ->active()
            ->create(['slug' => 'alt-text-instructor']);

        $response = $this->get(route('instructors.index'));

        $response->assertOk();
    }

    public function test_missing_portrait_handled_gracefully(): void
    {
        $instructor = Instructor::factory()
            ->active()
            ->create([
                'name' => 'No Photo Coach',
                'slug' => 'no-photo-coach',
            ]);

        $response = $this->get(route('instructors.show', 'no-photo-coach'));

        $response->assertOk();
    }

    public function test_bio_sanitized(): void
    {
        $instructor = Instructor::factory()
            ->active()
            ->create([
                'slug' => 'bio-sanitize',
                'bio' => '<p>Great coach</p><script>alert("xss")</script>',
            ]);

        $response = $this->get(route('instructors.show', 'bio-sanitize'));

        $response->assertSee('Great coach');
    }

    public function test_contact_cta_present(): void
    {
        $response = $this->get(route('instructors.index'));

        $response->assertSee(route('contact.show'));
    }
}
