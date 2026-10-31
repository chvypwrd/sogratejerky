<?php

namespace Database\Seeders;

use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

        $primaryItems = [
            ['label' => 'Home', 'type' => 'home', 'url' => '/', 'sort_order' => 0],
            ['label' => 'Shop Jerky', 'type' => 'custom', 'url' => '/shop', 'sort_order' => 1],
            ['label' => 'Galleries', 'type' => 'custom', 'url' => '/galleries', 'sort_order' => 2],
            ['label' => 'Testimonials', 'type' => 'custom', 'url' => '/testimonials', 'sort_order' => 3],
            ['label' => 'FAQs', 'type' => 'custom', 'url' => '/faqs', 'sort_order' => 4],
            ['label' => 'Blog', 'type' => 'custom', 'url' => '/blog', 'sort_order' => 5],
        ];

        $footerItems = [
            ['label' => 'Home', 'type' => 'home', 'url' => '/', 'sort_order' => 0],
            ['label' => 'Shop Jerky', 'type' => 'custom', 'url' => '/shop', 'sort_order' => 1],
            ['label' => 'Galleries', 'type' => 'custom', 'url' => '/galleries', 'sort_order' => 2],
            ['label' => 'Testimonials', 'type' => 'custom', 'url' => '/testimonials', 'sort_order' => 3],
            ['label' => 'FAQs', 'type' => 'custom', 'url' => '/faqs', 'sort_order' => 4],
            ['label' => 'Blog', 'type' => 'custom', 'url' => '/blog', 'sort_order' => 5],
            ['label' => 'Contact', 'type' => 'custom', 'url' => '/contact', 'sort_order' => 6],
        ];

        $this->seedItems($primary, $primaryItems);
        $this->seedItems($footer, $footerItems);
    }

    private function seedItems(Menu $menu, array $items): void
    {
        foreach ($items as $itemData) {
            MenuItem::firstOrCreate(
                ['menu_id' => $menu->id, 'slug' => Str::slug($itemData['label'])],
                array_merge($itemData, [
                    'menu_id' => $menu->id,
                    'target' => '_self',
                    'enabled' => true,
                ])
            );
        }
    }
}
