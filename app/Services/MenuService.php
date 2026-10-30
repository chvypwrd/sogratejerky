<?php

namespace App\Services;

use App\Enums\MenuLocation;
use App\Models\Gallery;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Spatie\Menu\Laravel\Menu as LaravelMenu;
use Spatie\Menu\Link;

class MenuService
{
    public function getMenu(MenuLocation $location): array
    {
        $cacheKey = "navigation.{$location->value}";

        return Cache::rememberForever($cacheKey, function () use ($location) {
            try {
                $menu = Menu::where('location', $location->value)->first();

                if (! $menu) {
                    return [];
                }

                return $this->buildMenuTree($menu->id);
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    public function getRenderedMenu(MenuLocation $location, ?string $activeRoute = null): string
    {
        $items = $this->getMenu($location);

        if (empty($items)) {
            return '';
        }

        $menu = LaravelMenu::new()->withoutWrapperTag();

        $this->buildSpatieMenu($menu, $items, $activeRoute);

        return $menu->render();
    }

    protected function buildSpatieMenu(LaravelMenu $menu, array $items, ?string $activeRoute): void
    {
        foreach ($items as $item) {
            if (! $this->isContentAvailable($item)) {
                continue;
            }

            $url = $this->resolveUrl($item);
            $link = $menu->add(
                Link::to($url, htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'))
                    ->addClass('nav-link')
                    ->setAttribute('target', $item['target'] ?? '_self')
            );

            if ($activeRoute && $url === route($activeRoute, [], false)) {
                $link->active();
            }

            if (! empty($item['children'])) {
                $subMenu = $link->addSubmenu(LaravelMenu::new()->withoutWrapperTag());
                $this->buildSpatieMenu($subMenu, $item['children'], $activeRoute);
            }
        }
    }

    public function buildMenuTree(int $menuId, ?int $parentId = null): array
    {
        $query = MenuItem::where('menu_id', $menuId)
            ->where('enabled', true)
            ->orderBy('sort_order');

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        $items = $query->get();

        $tree = [];

        foreach ($items as $item) {
            $node = $item->toArray();
            $node['children'] = $this->buildMenuTree($menuId, $item->id);
            $tree[] = $node;
        }

        return $tree;
    }

    public function resolveUrl(MenuItem|array $item): string
    {
        if (is_array($item)) {
            $item = (object) $item;
        }

        return match ($item->type) {
            'page' => isset($item->page_id) && $item->page_id
                ? (Page::find($item->page_id)?->slug ? route('pages.show', Page::find($item->page_id)->slug) : ($item->url ?? '/'))
                : ($item->url ?? '/'),
            'gallery' => isset($item->gallery_id) && $item->gallery_id
                ? (Gallery::find($item->gallery_id)?->slug ? route('gallery.show', Gallery::find($item->gallery_id)->slug) : ($item->url ?? '/'))
                : ($item->url ?? '/'),
            'home' => route('home'),
            default => $item->url ?? '/',
        };
    }

    public function isContentAvailable(MenuItem|array $item): bool
    {
        if (is_array($item)) {
            $item = (object) $item;
        }

        return match ($item->type) {
            'page' => isset($item->page_id) && $item->page_id
                ? (Page::find($item->page_id)?->isPublished() ?? false)
                : true,
            'gallery' => isset($item->gallery_id) && $item->gallery_id
                ? (Gallery::find($item->gallery_id)?->active ?? false)
                : true,
            default => true,
        };
    }

    public function invalidateCache(): void
    {
        foreach (MenuLocation::cases() as $location) {
            Cache::forget("navigation.{$location->value}");
        }
    }
}
