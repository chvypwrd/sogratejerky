<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageStatus;
use App\Enums\PageTemplate;
use App\Enums\RobotsDirective;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use App\Services\HtmlSanitizer;
use App\Services\PageSlugRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private HtmlSanitizer $sanitizer,
        private PageSlugRedirectService $slugService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Page::class);

        $filter = $request->get('filter', 'all');

        $query = Page::forIndex()->withCount('media')->with('creator');

        $query = match ($filter) {
            'draft' => $query->where('status', PageStatus::Draft),
            'published' => $query->where('status', PageStatus::Published)->where('published_at', '<=', now()),
            'trash' => Page::onlyTrashed()->withCount('media')->with('creator'),
            default => $query,
        };

        $pages = $query->latest()->paginate(20)->withQueryString();

        return view('admin.pages.index', compact('pages', 'filter'));
    }

    public function create(): View
    {
        $this->authorize('create', Page::class);

        return view('admin.pages.create', [
            'page' => new Page,
            'templates' => PageTemplate::cases(),
            'statuses' => PageStatus::cases(),
            'robotsDirectives' => RobotsDirective::cases(),
            'alt_text' => null,
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $this->authorize('create', Page::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['body'] = $this->sanitizer->clean($data['body'] ?? null);

        if (empty($data['status'])) {
            $data['status'] = PageStatus::Draft;
        }

        if (isset($data['status']) && $data['status'] === PageStatus::Published && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        unset($data['featured_media'], $data['alt_text']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $page = Page::create($data);

        if ($request->hasFile('featured_media')) {
            $page->addMediaFromRequest('featured_media')
                ->withCustomProperties(['alt_text' => $request->input('alt_text', '')])
                ->toMediaCollection('featured');
        }

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', 'Page created successfully.');
    }

    public function edit(Page $page): View
    {
        $this->authorize('update', $page);

        $altText = $page->getFirstMedia('featured')?->getCustomProperty('alt_text', '');

        return view('admin.pages.edit', [
            'page' => $page,
            'templates' => PageTemplate::cases(),
            'statuses' => PageStatus::cases(),
            'robotsDirectives' => RobotsDirective::cases(),
            'alt_text' => $altText,
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $this->authorize('update', $page);

        $oldSlug = $page->slug;
        $data = $request->validated();
        $data['body'] = $this->sanitizer->clean($data['body'] ?? null);
        $data['updated_by'] = $request->user()->id;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $page->update($data);

        $this->slugService->handleSlugChange($page, $oldSlug);

        if ($request->boolean('remove_featured_media')) {
            $page->clearMediaCollection('featured');
        } elseif ($request->hasFile('featured_media')) {
            $page->clearMediaCollection('featured');
            $page->addMediaFromRequest('featured_media')
                ->withCustomProperties(['alt_text' => $request->input('alt_text', '')])
                ->toMediaCollection('featured');
        } elseif ($page->hasMedia('featured')) {
            $media = $page->getFirstMedia('featured');
            $media->setCustomProperty('alt_text', $request->input('alt_text', ''));
            $media->save();
        }

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', 'Page updated successfully.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);

        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('status', 'Page moved to trash.');
    }

    public function restore(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);

        $page->restore();

        return redirect()->back()->with('status', 'Page restored successfully.');
    }

    public function forceDelete(Page $page): RedirectResponse
    {
        $this->authorize('delete', $page);

        $page->forceDelete();

        return redirect()
            ->route('admin.pages.index')
            ->with('status', 'Page permanently deleted.');
    }

    public function publish(Page $page): RedirectResponse
    {
        $this->authorize('publish', $page);

        if (is_null($page->published_at)) {
            $page->update(['published_at' => now()]);
        }

        $page->publish();

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', 'Page published successfully.');
    }

    public function unpublish(Page $page): RedirectResponse
    {
        $this->authorize('publish', $page);

        $page->unpublish();

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', 'Page unpublished successfully.');
    }
}
