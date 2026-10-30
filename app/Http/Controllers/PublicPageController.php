<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\PageSlugRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function __construct(
        private PageSlugRedirectService $slugService,
    ) {}

    public function __invoke(Request $request, string $slug): View|RedirectResponse
    {
        $redirectResult = $this->slugService->resolve($slug);

        if ($redirectResult !== null) {
            return redirect($redirectResult['to'], $redirectResult['status']);
        }

        $page = Page::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $template = match ($page->template->value) {
            'full_width' => 'pages.full_width',
            'landing' => 'pages.landing',
            default => 'pages.default',
        };

        return view($template, compact('page'));
    }
}
