<?php

namespace App\Services;

use App\Enums\RobotsDirective;
use App\Models\ClassOffering;
use App\Models\Gallery;
use App\Models\Instructor;
use App\Models\Page;
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
                'url' => $baseUrl.'/classes',
                'lastmod' => null,
            ];

            $classOfferings = ClassOffering::query()
                ->select(['slug', 'updated_at'])
                ->active()
                ->whereNotIn('robots', [
                    RobotsDirective::NoIndexFollow,
                    RobotsDirective::NoIndexNoFollow,
                ])
                ->orderBy('slug')
                ->get();

            foreach ($classOfferings as $class) {
                $urls[] = [
                    'url' => $baseUrl.'/classes/'.$class->slug,
                    'lastmod' => $class->updated_at?->format('Y-m-d'),
                ];
            }

            $urls[] = [
                'url' => $baseUrl.'/instructors',
                'lastmod' => null,
            ];

            $instructors = Instructor::query()
                ->select(['slug', 'updated_at'])
                ->active()
                ->whereNotIn('robots', [
                    RobotsDirective::NoIndexFollow,
                    RobotsDirective::NoIndexNoFollow,
                ])
                ->orderBy('slug')
                ->get();

            foreach ($instructors as $instructor) {
                $urls[] = [
                    'url' => $baseUrl.'/instructors/'.$instructor->slug,
                    'lastmod' => $instructor->updated_at?->format('Y-m-d'),
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
