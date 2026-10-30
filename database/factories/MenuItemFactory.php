<?php

namespace Database\Factories;

use App\Enums\MenuItemType;
use App\Enums\MenuTarget;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'parent_id' => null,
            'label' => \Faker\Factory::create()->words(2, true),
            'type' => MenuItemType::Custom,
            'page_id' => null,
            'url' => '/'.\Faker\Factory::create()->slug(),
            'target' => MenuTarget::Self,
            'sort_order' => 0,
            'enabled' => true,
        ];
    }
}
