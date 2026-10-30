<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PreviewController extends Controller
{
    public function show(Request $request, Page $page): View
    {
        $this->authorize('view', $page);

        return view('pages.preview', compact('page'));
    }

    public static function generatePreviewUrl(Page $page): string
    {
        return URL::temporarySignedRoute(
            'pages.preview',
            now()->addMinutes(30),
            ['page' => $page->id]
        );
    }
}
