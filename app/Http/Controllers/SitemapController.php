<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Response;

class SitemapController
{
    public function __invoke(SitemapService $sitemap): Response
    {
        $urls = $sitemap->getEligibleUrls();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $entry) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($entry['url']).'</loc>'."\n";
            if ($entry['lastmod']) {
                $xml .= '    <lastmod>'.e($entry['lastmod']).'</lastmod>'."\n";
            }
            $xml .= '  </url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
