<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RobotsDirective;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassOfferingRequest;
use App\Http\Requests\Admin\UpdateClassOfferingRequest;
use App\Models\ClassOffering;
use App\Services\HtmlSanitizer;
use App\Services\PageSlugRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassOfferingController extends Controller
{
    public function __construct(
        private HtmlSanitizer $sanitizer,
        private PageSlugRedirectService $slugService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClassOffering::class);

        $filter = $request->get('filter', 'all');

        $query = ClassOffering::withCount('media')->with('creator');

        $query = match ($filter) {
            'active' => $query->where('active', true),
            'inactive' => $query->where('active', false),
            'trash' => ClassOffering::onlyTrashed()->withCount('media')->with('creator'),
            default => $query,
        };

        $classes = $query->ordered()->paginate(20)->withQueryString();

        return view('admin.classes.index', compact('classes', 'filter'));
    }

    public function create(): View
    {
        $this->authorize('create', ClassOffering::class);

        return view('admin.classes.create', [
            'class' => new ClassOffering,
            'robotsDirectives' => RobotsDirective::cases(),
            'alt_text' => null,
        ]);
    }

    public function store(StoreClassOfferingRequest $request): RedirectResponse
    {
        $this->authorize('create', ClassOffering::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['description'] = $this->sanitizer->clean($data['description'] ?? null);

        unset($data['featured_media'], $data['alt_text']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $class = ClassOffering::create($data);

        if ($request->hasFile('featured_media')) {
            $class->addMediaFromRequest('featured_media')
                ->withCustomProperties(['alt_text' => $request->input('alt_text', '')])
                ->toMediaCollection('featured');
        }

        return redirect()
            ->route('admin.classes.edit', $class)
            ->with('status', 'Class offering created successfully.');
    }

    public function edit(ClassOffering $class): View
    {
        $this->authorize('update', $class);

        $altText = $class->getFirstMedia('featured')?->getCustomProperty('alt_text', '');

        return view('admin.classes.edit', [
            'class' => $class,
            'robotsDirectives' => RobotsDirective::cases(),
            'alt_text' => $altText,
        ]);
    }

    public function update(UpdateClassOfferingRequest $request, ClassOffering $class): RedirectResponse
    {
        $this->authorize('update', $class);

        $oldSlug = $class->slug;
        $data = $request->validated();
        $data['description'] = $this->sanitizer->clean($data['description'] ?? null);
        $data['updated_by'] = $request->user()->id;

        $class->update($data);

        $this->slugService->handleSlugChange($class, $oldSlug);

        if ($request->boolean('remove_featured_media')) {
            $class->clearMediaCollection('featured');
        } elseif ($request->hasFile('featured_media')) {
            $class->clearMediaCollection('featured');
            $class->addMediaFromRequest('featured_media')
                ->withCustomProperties(['alt_text' => $request->input('alt_text', '')])
                ->toMediaCollection('featured');
        } elseif ($class->hasMedia('featured')) {
            $media = $class->getFirstMedia('featured');
            $media->setCustomProperty('alt_text', $request->input('alt_text', ''));
            $media->save();
        }

        return redirect()
            ->route('admin.classes.edit', $class)
            ->with('status', 'Class offering updated successfully.');
    }

    public function destroy(ClassOffering $class): RedirectResponse
    {
        $this->authorize('delete', $class);

        $class->delete();

        return redirect()
            ->route('admin.classes.index')
            ->with('status', 'Class offering moved to trash.');
    }

    public function restore(ClassOffering $class): RedirectResponse
    {
        $this->authorize('delete', $class);

        $class->restore();

        return redirect()->back()->with('status', 'Class offering restored successfully.');
    }

    public function forceDelete(ClassOffering $class): RedirectResponse
    {
        $this->authorize('delete', $class);

        $class->clearMediaCollection('featured');
        $class->forceDelete();

        return redirect()
            ->route('admin.classes.index')
            ->with('status', 'Class offering permanently deleted.');
    }
}
