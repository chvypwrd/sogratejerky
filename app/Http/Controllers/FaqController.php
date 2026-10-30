<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::active()->ordered()->get();

        $grouped = $faqs->groupBy('category');

        return view('faqs.index', compact('faqs', 'grouped'));
    }
}
