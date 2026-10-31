<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function __invoke()
    {
        $testimonials = Testimonial::active()->featured()->ordered()->limit(3)->get();

        if ($testimonials->isEmpty()) {
            $testimonials = Testimonial::active()->ordered()->limit(3)->get();
        }

        $faqs = Faq::active()->ordered()->limit(6)->get();

        $featuredProducts = Product::catalog()
            ->featured()
            ->ordered()
            ->with(['media', 'availableVariants.media', 'availableVariants', 'categories'])
            ->limit(3)
            ->get();

        $recentPosts = BlogPost::published()
            ->with(['author', 'categories'])
            ->latestPublished()
            ->limit(3)
            ->get();

        return view('home', compact('testimonials', 'faqs', 'recentPosts', 'featuredProducts'));
    }
}
