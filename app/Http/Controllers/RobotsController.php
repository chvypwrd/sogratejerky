<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController
{
    public function __invoke(): Response
    {
        if (app()->environment('production')) {
            $content = "User-agent: *\n"
                ."Allow: /\n"
                ."Disallow: /admin\n"
                ."Disallow: /account\n"
                ."Disallow: /cart\n"
                ."Disallow: /checkout\n"
                ."Disallow: /login\n"
                ."Disallow: /register\n"
                ."Disallow: /forgot-password\n"
                ."Disallow: /reset-password\n"
                ."Disallow: /email\n\n"
                .'Sitemap: '.route('sitemap')."\n";
        } else {
            $content = "User-agent: *\nDisallow: /\n";
        }

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
