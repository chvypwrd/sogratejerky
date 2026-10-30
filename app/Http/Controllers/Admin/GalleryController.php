<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RobotsDirective;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGalleryRequest;
use App\Http\Requests\Admin\UpdateGalleryRequest;
use App\Models\Gallery;
use App\Services\PageSlugRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function __construct(
        private PageSlugRedirectService $slugService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Gallery::class);

        $filter = $request->get('filter', 'all');

        $query = Gallery::withCount('media')->with('creator');

        $query = match ($filter) {
            'active' => $query->where('active', true),
            'inactive' => $query->where('active', false),
            'trash' => Gallery::onlyTrashed()->withCount('media')->with('creator'),
            default => $query,
        };

        $galleries = $query->ordered()->paginate(20)->withQueryString();

        return view('admin.galleries.index', compact('galleries', 'filter'));
    }

    public function create(): View
    {
        $this->authorize('create', Gallery::class);

        return view('admin.galleries.create', [
            'gallery' => new Gallery,
            'robotsDirectives' => RobotsDirective::cases(),
        ]);
    }

    public function store(StoreGalleryRequest $request): RedirectResponse
    {
        $this->authorize('create', Gallery::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        unset($data['photos'], $data['alt_text'], $data['caption']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $gallery = Gallery::create($data);

        if ($request->hasFile('photos')) {
            $altText = $request->input('alt_text', '');
            $caption = $request->input('caption', '');

            foreach ($request->file('photos') as $photo) {
                $gallery->addMedia($photo)
                    ->withCustomProperties([
                        'alt_text' => $altText,
                        'caption' => $caption,
                        'is_cover' => $gallery->getMedia('photos')->count() === 0,
                    ])
                    ->toMediaCollection('photos');
            }
        }

        return redirect()
            ->route('admin.galleries.edit', $gallery)
            ->with('status', 'Gallery created successfully.');
    }

    public function edit(Gallery $gallery): View
    {
        $this->authorize('update', $gallery);

        return view('admin.galleries.edit', [
            'gallery' => $gallery,
            'robotsDirectives' => RobotsDirective::cases(),
        ]);
    }

    public function update(UpdateGalleryRequest $request, Gallery $gallery): RedirectResponse
    {
        $this->authorize('update', $gallery);

        $oldSlug = $gallery->slug;
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        unset($data['photos'], $data['alt_text'], $data['caption'], $data['remove_photo']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $gallery->update($data);

        $this->slugService->handleSlugChange($gallery, $oldSlug, '/gallery/');

        // Handle new photo uploads
        if ($request->hasFile('photos')) {
            $altText = $request->input('alt_text', '');
            $caption = $request->input('caption', '');

            foreach ($request->file('photos') as $photo) {
                $gallery->addMedia($photo)
                    ->withCustomProperties([
                        'alt_text' => $altText,
                        'caption' => $caption,
                        'is_cover' => false,
                    ])
                    ->toMediaCollection('photos');
            }
        }

        // Handle photo removal
        if ($request->filled('remove_photo')) {
            $media = $gallery->getMedia('photos')->firstWhere('id', $request->input('remove_photo'));

            if ($media) {
                $media->delete();
            }
        }

        // Handle alt_text editing for existing photos
        if ($request->filled('photo_alt_text')) {
            foreach ($request->input('photo_alt_text') as $mediaId => $altText) {
                $media = $gallery->getMedia('photos')->firstWhere('id', $mediaId);

                if ($media) {
                    $media->setCustomProperty('alt_text', $altText);
                    $media->save();
                }
            }
        }

        // Handle caption editing for existing photos
        if ($request->filled('photo_caption')) {
            foreach ($request->input('photo_caption') as $mediaId => $caption) {
                $media = $gallery->getMedia('photos')->firstWhere('id', $mediaId);

                if ($media) {
                    $media->setCustomProperty('caption', $caption);
                    $media->save();
                }
            }
        }

        // Handle cover selection
        if ($request->filled('cover_media_id')) {
            $coverId = $request->input('cover_media_id');

            foreach ($gallery->getMedia('photos') as $media) {
                $media->setCustomProperty('is_cover', $media->id == $coverId);
                $media->save();
            }
        }

        return redirect()
            ->route('admin.galleries.edit', $gallery)
            ->with('status', 'Gallery updated successfully.');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        $this->authorize('delete', $gallery);

        $gallery->delete();

        return redirect()
            ->route('admin.galleries.index')
            ->with('status', 'Gallery moved to trash.');
    }

    public function restore(Gallery $gallery): RedirectResponse
    {
        $this->authorize('delete', $gallery);

        $gallery->restore();

        return redirect()->back()->with('status', 'Gallery restored successfully.');
    }

    public function forceDelete(Gallery $gallery): RedirectResponse
    {
        $this->authorize('delete', $gallery);

        $gallery->clearMediaCollection('photos');
        $gallery->forceDelete();

        return redirect()
            ->route('admin.galleries.index')
            ->with('status', 'Gallery permanently deleted.');
    }

    public function reorder(Request $request, Gallery $gallery): RedirectResponse
    {
        $this->authorize('update', $gallery);

        $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|exists:media,id',
        ]);

        $order = $request->input('order');

        if (count($order) !== count(array_unique($order))) {
            return redirect()->back()
                ->withErrors(['order' => 'The order contains duplicate items.'])
                ->withInput();
        }

        $photoIds = $gallery->getMedia('photos')->pluck('id')->toArray();
        $invalidIds = array_diff($order, $photoIds);

        if (! empty($invalidIds)) {
            return redirect()->back()
                ->withErrors(['order' => 'One or more media items do not belong to this gallery.'])
                ->withInput();
        }

        foreach ($order as $position => $mediaId) {
            $media = $gallery->getMedia('photos')->firstWhere('id', $mediaId);
            $media->order_column = $position + 1;
            $media->save();
        }

        return redirect()->back()->with('status', 'Photos reordered successfully.');
    }
}
