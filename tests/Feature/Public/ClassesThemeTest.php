<?php

namespace Tests\Feature\Public;

use App\Models\ClassOffering;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassesThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_classes_index_returns_200(): void
    {
        $response = $this->get(route('classes.index'));

        $response->assertOk();
    }

    public function test_active_classes_listed(): void
    {
        $class = ClassOffering::factory()
            ->active()
            ->create(['title' => 'Morning Kickboxing']);

        $response = $this->get(route('classes.index'));

        $response->assertSee($class->title);
    }

    public function test_inactive_classes_hidden(): void
    {
        $class = ClassOffering::factory()
            ->inactive()
            ->create(['title' => 'Hidden Class']);

        $response = $this->get(route('classes.index'));

        $response->assertDontSee('Hidden Class');
    }

    public function test_class_card_markup_present(): void
    {
        ClassOffering::factory()
            ->active()
            ->create(['title' => 'Card Test Class']);

        $response = $this->get(route('classes.index'));

        $response->assertSee('card');
    }

    public function test_class_detail_page_returns_200(): void
    {
        $class = ClassOffering::factory()
            ->active()
            ->create(['slug' => 'detail-test']);

        $response = $this->get(route('classes.show', 'detail-test'));

        $response->assertOk();
        $response->assertSee($class->title);
    }

    public function test_missing_image_handled_gracefully(): void
    {
        $class = ClassOffering::factory()
            ->active()
            ->create([
                'title' => 'No Image Class',
                'slug' => 'no-image-class',
            ]);

        $response = $this->get(route('classes.show', 'no-image-class'));

        $response->assertOk();
    }

    public function test_cta_to_contact_present(): void
    {
        $response = $this->get(route('classes.index'));

        $response->assertSee(route('contact.show'));
    }

    public function test_seo_metadata_present(): void
    {
        $class = ClassOffering::factory()
            ->active()
            ->create([
                'title' => 'SEO Class',
                'slug' => 'seo-class',
                'meta_title' => 'SEO Class Title',
                'meta_description' => 'This is a test meta description for SEO purposes',
            ]);

        $response = $this->get(route('classes.show', 'seo-class'));

        $response->assertSee('og:type');
        $response->assertSee('og:title');
        $response->assertSee('canonical');
    }
}
