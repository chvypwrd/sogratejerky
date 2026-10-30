<?php

namespace Tests\Feature\Cms;

use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use App\Services\MenuService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MenuAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createMenu(string $location = 'primary'): Menu
    {
        return Menu::create([
            'name' => fake()->words(2, true),
            'location' => $location,
        ]);
    }

    protected function createMenuItem(array $overrides = []): MenuItem
    {
        $defaults = [
            'label' => fake()->words(2, true),
            'type' => 'custom',
            'url' => '/'.fake()->slug(),
            'target' => '_self',
            'sort_order' => 0,
            'enabled' => true,
        ];

        return MenuItem::create(array_merge($defaults, $overrides));
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get(route('admin.menus.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_menus_view_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.menus.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_view_menus(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->get(route('admin.menus.index'))
            ->assertOk();
    }

    public function test_editor_cannot_view_menus(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');

        $this->actingAs($user)->get(route('admin.menus.index'))
            ->assertForbidden();
    }

    public function test_can_store_custom_menu_item(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();

        $this->actingAs($user)->post(route('admin.menus.items.store', $menu), [
            'label' => 'About Us',
            'type' => 'custom',
            'url' => '/about',
            'target' => '_self',
            'enabled' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'label' => 'About Us',
            'type' => 'custom',
            'url' => '/about',
        ]);
    }

    public function test_can_store_page_menu_item(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();
        $page = Page::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)->post(route('admin.menus.items.store', $menu), [
            'label' => 'Home Page',
            'type' => 'page',
            'page_id' => $page->id,
            'enabled' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'label' => 'Home Page',
            'type' => 'page',
            'page_id' => $page->id,
        ]);
    }

    public function test_can_update_menu_item(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();
        $item = $this->createMenuItem([
            'menu_id' => $menu->id,
            'label' => 'Original Label',
            'url' => '/original',
        ]);

        $this->actingAs($user)->put(route('admin.menus.items.update', [$menu, $item]), [
            'label' => 'Updated Label',
            'type' => 'custom',
            'url' => '/updated',
            'enabled' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('menu_items', [
            'id' => $item->id,
            'label' => 'Updated Label',
            'url' => '/updated',
        ]);
    }

    public function test_can_delete_menu_item(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();
        $item = $this->createMenuItem(['menu_id' => $menu->id]);

        $this->actingAs($user)->delete(route('admin.menus.items.destroy', [$menu, $item]))
            ->assertRedirect();

        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
    }

    public function test_can_reorder_menu_items(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();

        $item1 = $this->createMenuItem(['menu_id' => $menu->id, 'sort_order' => 0]);
        $item2 = $this->createMenuItem(['menu_id' => $menu->id, 'sort_order' => 1]);

        $this->actingAs($user)->patch(route('admin.menus.order', $menu), [
            'items' => [
                ['id' => $item1->id, 'sort_order' => 1, 'parent_id' => null],
                ['id' => $item2->id, 'sort_order' => 0, 'parent_id' => null],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('menu_items', ['id' => $item1->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('menu_items', ['id' => $item2->id, 'sort_order' => 0]);
    }

    public function test_invalid_menu_type_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();

        $this->actingAs($user)->post(route('admin.menus.items.store', $menu), [
            'label' => 'Bad Type',
            'type' => 'invalid_type',
            'url' => '/bad',
        ])->assertSessionHasErrors('type');
    }

    public function test_unsafe_custom_url_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();

        $this->actingAs($user)->post(route('admin.menus.items.store', $menu), [
            'label' => 'XSS Link',
            'type' => 'custom',
            'url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('url');
    }

    public function test_external_url_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();

        $this->actingAs($user)->post(route('admin.menus.items.store', $menu), [
            'label' => 'External Link',
            'type' => 'custom',
            'url' => 'https://example.com',
        ])->assertSessionHasErrors('url');
    }

    public function test_self_parent_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();
        $item = $this->createMenuItem(['menu_id' => $menu->id]);

        $this->actingAs($user)->put(route('admin.menus.items.update', [$menu, $item]), [
            'label' => $item->label,
            'type' => 'custom',
            'url' => '/self-parent',
            'parent_id' => $item->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_cross_menu_parent_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu1 = $this->createMenu('primary');
        $menu2 = $this->createMenu('footer');
        $parentItem = $this->createMenuItem(['menu_id' => $menu2->id]);

        $this->actingAs($user)->post(route('admin.menus.items.store', $menu1), [
            'label' => 'Cross Menu',
            'type' => 'custom',
            'url' => '/cross',
            'parent_id' => $parentItem->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_cycle_prevention(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();

        $parent = $this->createMenuItem(['menu_id' => $menu->id, 'parent_id' => null]);
        $child = $this->createMenuItem(['menu_id' => $menu->id, 'parent_id' => $parent->id]);

        $this->actingAs($user)->put(route('admin.menus.items.update', [$menu, $parent]), [
            'label' => $parent->label,
            'type' => 'custom',
            'url' => '/cycle',
            'parent_id' => $child->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_depth_limit_enforced(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();

        $parent = $this->createMenuItem(['menu_id' => $menu->id, 'parent_id' => null]);
        $child = $this->createMenuItem(['menu_id' => $menu->id, 'parent_id' => $parent->id]);

        $this->actingAs($user)->post(route('admin.menus.items.store', $menu), [
            'label' => 'Too Deep',
            'type' => 'custom',
            'url' => '/deep',
            'parent_id' => $child->id,
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_parent_with_children_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();

        $parent = $this->createMenuItem(['menu_id' => $menu->id]);
        $child = $this->createMenuItem(['menu_id' => $menu->id, 'parent_id' => $parent->id]);

        $this->actingAs($user)->delete(route('admin.menus.items.destroy', [$menu, $parent]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('menu_items', ['id' => $parent->id]);
    }

    public function test_cache_invalidated_on_item_create(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();

        $menuService = app(MenuService::class);

        $menuService->getMenu(MenuLocation::Primary);
        $this->assertNotNull(Cache::get('navigation.primary'));

        $this->actingAs($user)->post(route('admin.menus.items.store', $menu), [
            'label' => 'New Item',
            'type' => 'custom',
            'url' => '/new-item',
            'enabled' => true,
        ])->assertRedirect();

        $this->assertNull(Cache::get('navigation.primary'));

        $items = $menuService->getMenu(MenuLocation::Primary);
        $this->assertNotEmpty($items);
        $this->assertEquals('New Item', $items[0]['label']);
    }

    public function test_cache_invalidated_on_item_update(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();
        $item = $this->createMenuItem([
            'menu_id' => $menu->id,
            'label' => 'Original',
            'url' => '/original',
        ]);

        $menuService = app(MenuService::class);

        $menuService->getMenu(MenuLocation::Primary);
        $this->assertNotNull(Cache::get('navigation.primary'));

        $this->actingAs($user)->put(route('admin.menus.items.update', [$menu, $item]), [
            'label' => 'Updated',
            'type' => 'custom',
            'url' => '/updated',
            'enabled' => true,
        ])->assertRedirect();

        $this->assertNull(Cache::get('navigation.primary'));

        $items = $menuService->getMenu(MenuLocation::Primary);
        $this->assertEquals('Updated', $items[0]['label']);
    }

    public function test_cache_invalidated_on_item_delete(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();
        $item = $this->createMenuItem(['menu_id' => $menu->id, 'label' => 'To Delete']);

        $menuService = app(MenuService::class);

        $menuService->getMenu(MenuLocation::Primary);
        $this->assertNotNull(Cache::get('navigation.primary'));

        $this->actingAs($user)->delete(route('admin.menus.items.destroy', [$menu, $item]))
            ->assertRedirect();

        $this->assertNull(Cache::get('navigation.primary'));

        $items = $menuService->getMenu(MenuLocation::Primary);
        $this->assertEmpty($items);
    }

    public function test_cache_invalidated_on_reorder(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');
        $menu = $this->createMenu();
        $item1 = $this->createMenuItem(['menu_id' => $menu->id, 'label' => 'First', 'sort_order' => 0]);
        $item2 = $this->createMenuItem(['menu_id' => $menu->id, 'label' => 'Second', 'sort_order' => 1]);

        $menuService = app(MenuService::class);

        $items = $menuService->getMenu(MenuLocation::Primary);
        $this->assertEquals('First', $items[0]['label']);
        $this->assertEquals('Second', $items[1]['label']);

        $this->actingAs($user)->patch(route('admin.menus.order', $menu), [
            'items' => [
                ['id' => $item1->id, 'sort_order' => 1, 'parent_id' => null],
                ['id' => $item2->id, 'sort_order' => 0, 'parent_id' => null],
            ],
        ])->assertRedirect();

        $this->assertNull(Cache::get('navigation.primary'));

        $items = $menuService->getMenu(MenuLocation::Primary);
        $this->assertEquals('Second', $items[0]['label']);
        $this->assertEquals('First', $items[1]['label']);
    }

    public function test_unauthorized_reorder_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');
        $menu = $this->createMenu();

        $this->actingAs($user)->patch(route('admin.menus.order', $menu), [
            'items' => [],
        ])->assertForbidden();
    }
}
