<?php

namespace App\Services;

use App\Enums\RobotsDirective;
use App\Models\BlogPost;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use Illuminate\Support\Facades\Cache;

class SitemapService
{
    public function __construct(
        protected SettingsService $settings,
    ) {}

    public function getEligibleUrls(): array
    {
        return Cache::remember('sitemap.xml', now()->addHour(), function (): array {
            $urls = [];
            $baseUrl = config('app.url');

            $urls[] = [
                'url' => $baseUrl,
                'lastmod' => null,
            ];

            $urls[] = [
                'url' => $baseUrl.'/shop',
                'lastmod' => null,
            ];

            $products = Product::query()
                ->catalog()
                ->select(['slug', 'updated_at'])
                ->orderBy('slug')
                ->get();

            foreach ($products as $product) {
                $urls[] = [
                    'url' => $baseUrl.'/shop/'.$product->slug,
                    'lastmod' => $product->updated_at?->format('Y-m-d'),
                ];
            }

            $productCategories = ProductCategory::query()
                ->active()
                ->whereHas('products', fn ($query) => $query->catalog())
                ->select(['slug', 'updated_at'])
                ->orderBy('slug')
                ->get();

            foreach ($productCategories as $category) {
                $urls[] = [
                    'url' => $baseUrl.'/shop/category/'.$category->slug,
                    'lastmod' => $category->updated_at?->format('Y-m-d'),
                ];
            }

            $productTags = ProductTag::query()
                ->whereHas('products', fn ($query) => $query->catalog())
                ->select(['slug', 'updated_at'])
                ->orderBy('slug')
                ->get();

            foreach ($productTags as $tag) {
                $urls[] = [
                    'url' => $baseUrl.'/shop/tag/'.$tag->slug,
                    'lastmod' => $tag->updated_at?->format('Y-m-d'),
                ];
            }

            $urls[] = [
                'url' => $baseUrl.'/blog',
                'lastmod' => null,
            ];

            $blogPosts = BlogPost::query()
                ->select(['slug', 'updated_at'])
                ->published()
                ->orderBy('slug')
                ->get();

            foreach ($blogPosts as $blogPost) {
                $urls[] = [
                    'url' => $baseUrl.'/blog/'.$blogPost->slug,
                    'lastmod' => $blogPost->updated_at?->format('Y-m-d'),
                ];
            }

            $urls[] = [
                'url' => $baseUrl.'/faq',
                'lastmod' => null,
            ];

            $urls[] = [
                'url' => $baseUrl.'/gallery',
                'lastmod' => null,
            ];

            $galleries = Gallery::query()
                ->select(['slug', 'updated_at'])
                ->active()
                ->whereNotIn('robots', [
                    RobotsDirective::NoIndexFollow,
                    RobotsDirective::NoIndexNoFollow,
                ])
                ->orderBy('slug')
                ->get();

            foreach ($galleries as $gallery) {
                $urls[] = [
                    'url' => $baseUrl.'/gallery/'.$gallery->slug,
                    'lastmod' => $gallery->updated_at?->format('Y-m-d'),
                ];
            }

            $urls[] = [
                'url' => $baseUrl.'/contact',
                'lastmod' => null,
            ];

            $pages = Page::query()
                ->select(['slug', 'updated_at'])
                ->published()
                ->whereNotIn('robots', [
                    RobotsDirective::NoIndexFollow,
                    RobotsDirective::NoIndexNoFollow,
                ])
                ->orderBy('slug')
                ->get();

            foreach ($pages as $page) {
                $urls[] = [
                    'url' => $baseUrl.'/'.$page->slug,
                    'lastmod' => $page->updated_at?->format('Y-m-d'),
                ];
            }

            return $urls;
        });
    }
}
