<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController
{
    public function __invoke(): Response
    {
        if (app()->environment('production')) {
            $content = "User-agent: *\nAllow: /\n\nSitemap: ".route('sitemap')."\n";
        } else {
            $content = "User-agent: *\nDisallow: /\n";
        }

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
