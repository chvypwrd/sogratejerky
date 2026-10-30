<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Services\PageSlugRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicGalleryController extends Controller
{
    public function __construct(
        private PageSlugRedirectService $slugService,
    ) {}

    public function index(): View
    {
        $galleries = Gallery::forIndex()
            ->with('media')
            ->get();

        return view('gallery.index', compact('galleries'));
    }

    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $redirectResult = $this->slugService->resolve('/gallery/'.$slug);

        if ($redirectResult !== null) {
            return redirect($redirectResult['to'], $redirectResult['status']);
        }

        $gallery = Gallery::active()
            ->with('media')
            ->where('slug', $slug)
            ->firstOrFail();

        return view('gallery.show', compact('gallery'));
    }
}
