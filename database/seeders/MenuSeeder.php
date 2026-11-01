<?php

namespace Database\Seeders;

use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\MenuService;
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

        $primaryItems = [
            ['label' => 'Home', 'type' => 'home', 'url' => '/', 'sort_order' => 0],
            ['label' => 'Shop Jerky', 'type' => 'custom', 'url' => '/shop', 'sort_order' => 1],
            ['label' => 'Quick Order', 'type' => 'custom', 'url' => '/quick-order', 'sort_order' => 2],
            ['label' => 'Galleries', 'type' => 'custom', 'url' => '/galleries', 'sort_order' => 3],
            ['label' => 'Testimonials', 'type' => 'custom', 'url' => '/testimonials', 'sort_order' => 4],
            ['label' => 'FAQs', 'type' => 'custom', 'url' => '/faqs', 'sort_order' => 5],
            ['label' => 'Blog', 'type' => 'custom', 'url' => '/blog', 'sort_order' => 6],
            ['label' => 'Cart', 'type' => 'custom', 'url' => '/cart', 'sort_order' => 7],
        ];

        if ($aboutPage = Page::where('slug', 'about')->first()) {
            $primaryItems[] = ['label' => 'About', 'type' => 'page', 'page_id' => $aboutPage->id, 'url' => '/about', 'sort_order' => 8];
        }

        $primaryItems[] = ['label' => 'Contact', 'type' => 'custom', 'url' => '/contact', 'sort_order' => 9];

        $footerItems = [
            ['label' => 'Home', 'type' => 'home', 'url' => '/', 'sort_order' => 0],
            ['label' => 'Shop Jerky', 'type' => 'custom', 'url' => '/shop', 'sort_order' => 1],
            ['label' => 'Quick Order', 'type' => 'custom', 'url' => '/quick-order', 'sort_order' => 2],
            ['label' => 'Galleries', 'type' => 'custom', 'url' => '/galleries', 'sort_order' => 3],
            ['label' => 'Testimonials', 'type' => 'custom', 'url' => '/testimonials', 'sort_order' => 4],
            ['label' => 'FAQs', 'type' => 'custom', 'url' => '/faqs', 'sort_order' => 5],
            ['label' => 'Blog', 'type' => 'custom', 'url' => '/blog', 'sort_order' => 6],
            ['label' => 'Cart', 'type' => 'custom', 'url' => '/cart', 'sort_order' => 7],
            ['label' => 'Contact', 'type' => 'custom', 'url' => '/contact', 'sort_order' => 8],
        ];

        if ($aboutPage) {
            $footerItems[] = ['label' => 'About Us', 'type' => 'page', 'page_id' => $aboutPage->id, 'url' => '/about', 'sort_order' => 9];
        }

        foreach ([
            'privacy-policy' => ['Privacy Policy', 10],
            'terms-and-conditions' => ['Terms & Conditions', 11],
            'shipping-policy' => ['Shipping Policy', 12],
            'refund-policy' => ['Refund Policy', 13],
        ] as $slug => [$label, $sortOrder]) {
            if ($page = Page::where('slug', $slug)->first()) {
                $footerItems[] = ['label' => $label, 'type' => 'page', 'page_id' => $page->id, 'url' => '/'.$slug, 'sort_order' => $sortOrder];
            }
        }

        $this->seedItems($primary, $primaryItems);
        $this->seedItems($footer, $footerItems);

        app(MenuService::class)->invalidateCache();
    }

    private function seedItems(Menu $menu, array $items): void
    {
        foreach ($items as $itemData) {
            $lookup = ['menu_id' => $menu->id];

            $lookup = match ($itemData['type']) {
                'page' => $lookup + ['page_id' => $itemData['page_id']],
                'gallery' => $lookup + ['gallery_id' => $itemData['gallery_id']],
                'home' => $lookup + ['type' => 'home'],
                default => $lookup + ['url' => $itemData['url']],
            };

            MenuItem::firstOrCreate(
                $lookup,
                array_merge($itemData, [
                    'menu_id' => $menu->id,
                    'target' => '_self',
                    'enabled' => true,
                ])
            );
        }
    }
}
