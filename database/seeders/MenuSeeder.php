<?php

namespace Database\Seeders;

use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $primary = Menu::firstOrCreate(
            ['location' => MenuLocation::Primary],
            ['name' => 'Primary Navigation']
        );

        $footer = Menu::firstOrCreate(
            ['location' => MenuLocation::Footer],
            ['name' => 'Footer Navigation']
        );

        // Primary menu items
        $items = [
            ['label' => 'Home', 'type' => 'home', 'url' => '/', 'sort_order' => 0],
            ['label' => 'Classes', 'type' => 'custom', 'url' => '/classes', 'sort_order' => 1],
            ['label' => 'Instructors', 'type' => 'custom', 'url' => '/instructors', 'sort_order' => 2],
            ['label' => 'Galleries', 'type' => 'custom', 'url' => '/galleries', 'sort_order' => 3],
            ['label' => 'Testimonials', 'type' => 'custom', 'url' => '/testimonials', 'sort_order' => 4],
            ['label' => 'FAQs', 'type' => 'custom', 'url' => '/faqs', 'sort_order' => 5],
        ];

        foreach ($items as $itemData) {
            MenuItem::firstOrCreate(
                ['menu_id' => $primary->id, 'slug' => \Illuminate\Support\Str::slug($itemData['label'])],
                array_merge($itemData, [
                    'menu_id' => $primary->id,
                    'target' => '_self',
                    'enabled' => true,
                ])
            );
        }
    }
}
