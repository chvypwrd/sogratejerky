<?php

namespace Tests\Feature\Cms;

use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\MenuService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuPublicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createMenuWithItems(MenuLocation $location, array $itemData): Menu
    {
        $menu = Menu::create([
            'name' => fake()->words(2, true),
            'location' => $location->value,
        ]);

        foreach ($itemData as $index => $data) {
            $data['menu_id'] = $menu->id;
            $data['sort_order'] = $data['sort_order'] ?? $index;
            $data['enabled'] = $data['enabled'] ?? true;
            $data['type'] = $data['type'] ?? 'custom';
            $data['label'] = $data['label'] ?? fake()->words(2, true);
            $data['url'] = $data['url'] ?? '/'.fake()->slug();
            $data['parent_id'] = $data['parent_id'] ?? null;
            $data['target'] = $data['target'] ?? '_self';
            MenuItem::create($data);
        }

        return $menu;
    }

    public function test_primary_menu_renders_in_header(): void
    {
        $this->createMenuWithItems(MenuLocation::Primary, [
            ['label' => 'Home', 'url' => '/', 'sort_order' => 0],
            ['label' => 'Galleries', 'url' => '/galleries', 'sort_order' => 1],
        ]);

        $service = app(MenuService::class);
        $rendered = $service->getRenderedMenu(MenuLocation::Primary);

        $this->assertNotEmpty($rendered);
        $this->assertStringContainsString('Home', $rendered);
        $this->assertStringContainsString('Galleries', $rendered);
    }

    public function test_footer_menu_renders(): void
    {
        $this->createMenuWithItems(MenuLocation::Footer, [
            ['label' => 'Privacy Policy', 'url' => '/privacy', 'sort_order' => 0],
            ['label' => 'Terms', 'url' => '/terms', 'sort_order' => 1],
        ]);

        $service = app(MenuService::class);
        $rendered = $service->getRenderedMenu(MenuLocation::Footer);

        $this->assertNotEmpty($rendered);
        $this->assertStringContainsString('Privacy Policy', $rendered);
        $this->assertStringContainsString('Terms', $rendered);
    }

    public function test_order_correct(): void
    {
        $this->createMenuWithItems(MenuLocation::Primary, [
            ['label' => 'Third', 'url' => '/third', 'sort_order' => 2],
            ['label' => 'First', 'url' => '/first', 'sort_order' => 0],
            ['label' => 'Second', 'url' => '/second', 'sort_order' => 1],
        ]);

        $service = app(MenuService::class);
        $rendered = $service->getRenderedMenu(MenuLocation::Primary);

        $firstPos = strpos($rendered, 'First');
        $secondPos = strpos($rendered, 'Second');
        $thirdPos = strpos($rendered, 'Third');

        $this->assertTrue($firstPos < $secondPos);
        $this->assertTrue($secondPos < $thirdPos);
    }

    public function test_disabled_items_hidden(): void
    {
        $menu = Menu::create([
            'name' => 'Primary Nav',
            'location' => MenuLocation::Primary->value,
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'label' => 'Visible Item',
            'type' => 'custom',
            'url' => '/visible',
            'sort_order' => 0,
            'enabled' => true,
            'parent_id' => null,
            'target' => '_self',
        ]);

        MenuItem::create([
            'menu_id' => $menu->id,
            'label' => 'Hidden Item',
            'type' => 'custom',
            'url' => '/hidden',
            'sort_order' => 1,
            'enabled' => false,
            'parent_id' => null,
            'target' => '_self',
        ]);

        $service = app(MenuService::class);
        $rendered = $service->getRenderedMenu(MenuLocation::Primary);

        $this->assertStringContainsString('Visible Item', $rendered);
        $this->assertStringNotContainsString('Hidden Item', $rendered);
    }

    public function test_unavailable_content_hidden_with_deleted_page(): void
    {
        $menu = Menu::create([
            'name' => 'Primary Nav',
            'location' => MenuLocation::Primary->value,
        ]);

        $page = Page::factory()->draft()->create([
            'created_by' => null,
            'slug' => 'draft-page',
        ]);

        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'label' => 'Draft Page',
            'type' => 'page',
            'page_id' => $page->id,
            'sort_order' => 0,
            'enabled' => true,
            'parent_id' => null,
            'target' => '_self',
        ]);

        $service = app(MenuService::class);
        $this->assertFalse($service->isContentAvailable($item));
    }

    public function test_labels_escaped(): void
    {
        $this->createMenuWithItems(MenuLocation::Primary, [
            ['label' => '<script>alert("xss")</script>', 'url' => '/xss', 'sort_order' => 0],
        ]);

        $service = app(MenuService::class);
        $rendered = $service->getRenderedMenu(MenuLocation::Primary);

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $rendered);
    }

    public function test_blank_target_renders_correctly(): void
    {
        $this->createMenuWithItems(MenuLocation::Primary, [
            ['label' => 'External', 'url' => '/external', 'target' => '_blank', 'sort_order' => 0],
        ]);

        $service = app(MenuService::class);
        $rendered = $service->getRenderedMenu(MenuLocation::Primary);

        $this->assertStringContainsString('target="_blank"', $rendered);
    }

    public function test_missing_menu_fails_gracefully(): void
    {
        $service = app(MenuService::class);
        $rendered = $service->getRenderedMenu(MenuLocation::Primary);

        $this->assertSame('', $rendered);
    }
}
