<?php

namespace App\Http\Controllers;

use App\Models\Faq;
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

        return view('home', compact('testimonials', 'faqs'));
    }
}
