<?php

namespace App\Http\Controllers;

use App\Models\ClassOffering;
use App\Models\Faq;
use App\Models\Instructor;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function __invoke()
    {
        $featuredClasses = ClassOffering::active()->featured()->ordered()->limit(3)->get();

        if ($featuredClasses->isEmpty()) {
            $featuredClasses = ClassOffering::active()->ordered()->limit(3)->get();
        }

        $instructors = Instructor::active()->ordered()->limit(1)->get();

        $testimonials = Testimonial::active()->featured()->ordered()->limit(3)->get();

        if ($testimonials->isEmpty()) {
            $testimonials = Testimonial::active()->ordered()->limit(3)->get();
        }

        $faqs = Faq::active()->ordered()->limit(6)->get();

        return view('home', compact('featuredClasses', 'instructors', 'testimonials', 'faqs'));
    }
}
