<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(): View
    {
        $testimonials = Testimonial::active()->orderBy('sort_order')->get();

        return view('testimonials.index', compact('testimonials'));
    }
}
