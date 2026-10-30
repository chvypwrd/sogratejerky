<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RobotsDirective;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInstructorRequest;
use App\Http\Requests\Admin\UpdateInstructorRequest;
use App\Models\Instructor;
use App\Services\HtmlSanitizer;
use App\Services\PageSlugRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function __construct(
        private HtmlSanitizer $sanitizer,
        private PageSlugRedirectService $slugService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Instructor::class);

        $filter = $request->get('filter', 'all');

        $query = Instructor::withCount('media')->with('creator');

        $query = match ($filter) {
            'active' => $query->where('active', true),
            'inactive' => $query->where('active', false),
            'trash' => Instructor::onlyTrashed()->withCount('media')->with('creator'),
            default => $query,
        };

        $instructors = $query->ordered()->paginate(20)->withQueryString();

        return view('admin.instructors.index', compact('instructors', 'filter'));
    }

    public function create(): View
    {
        $this->authorize('create', Instructor::class);

        return view('admin.instructors.create', [
            'instructor' => new Instructor,
            'robotsDirectives' => RobotsDirective::cases(),
            'alt_text' => null,
        ]);
    }

    public function store(StoreInstructorRequest $request): RedirectResponse
    {
        $this->authorize('create', Instructor::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['bio'] = $this->sanitizer->clean($data['bio'] ?? null);

        unset($data['featured_media'], $data['alt_text']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $instructor = Instructor::create($data);

        if ($request->hasFile('featured_media')) {
            $instructor->addMediaFromRequest('featured_media')
                ->withCustomProperties(['alt_text' => $request->input('alt_text', '')])
                ->toMediaCollection('portrait');
        }

        return redirect()
            ->route('admin.instructors.edit', $instructor)
            ->with('status', 'Instructor created successfully.');
    }

    public function edit(Instructor $instructor): View
    {
        $this->authorize('update', $instructor);

        $altText = $instructor->getFirstMedia('portrait')?->getCustomProperty('alt_text', '');

        return view('admin.instructors.edit', [
            'instructor' => $instructor,
            'robotsDirectives' => RobotsDirective::cases(),
            'alt_text' => $altText,
        ]);
    }

    public function update(UpdateInstructorRequest $request, Instructor $instructor): RedirectResponse
    {
        $this->authorize('update', $instructor);

        $oldSlug = $instructor->slug;
        $data = $request->validated();
        $data['bio'] = $this->sanitizer->clean($data['bio'] ?? null);
        $data['updated_by'] = $request->user()->id;

        $instructor->update($data);

        $this->slugService->handleSlugChange($instructor, $oldSlug);

        if ($request->boolean('remove_featured_media')) {
            $instructor->clearMediaCollection('portrait');
        } elseif ($request->hasFile('featured_media')) {
            $instructor->clearMediaCollection('portrait');
            $instructor->addMediaFromRequest('featured_media')
                ->withCustomProperties(['alt_text' => $request->input('alt_text', '')])
                ->toMediaCollection('portrait');
        } elseif ($instructor->hasMedia('portrait')) {
            $media = $instructor->getFirstMedia('portrait');
            $media->setCustomProperty('alt_text', $request->input('alt_text', ''));
            $media->save();
        }

        return redirect()
            ->route('admin.instructors.edit', $instructor)
            ->with('status', 'Instructor updated successfully.');
    }

    public function destroy(Instructor $instructor): RedirectResponse
    {
        $this->authorize('delete', $instructor);

        $instructor->delete();

        return redirect()
            ->route('admin.instructors.index')
            ->with('status', 'Instructor moved to trash.');
    }

    public function restore(Instructor $instructor): RedirectResponse
    {
        $this->authorize('delete', $instructor);

        $instructor->restore();

        return redirect()->back()->with('status', 'Instructor restored successfully.');
    }

    public function forceDelete(Instructor $instructor): RedirectResponse
    {
        $this->authorize('delete', $instructor);

        $instructor->clearMediaCollection('portrait');
        $instructor->forceDelete();

        return redirect()
            ->route('admin.instructors.index')
            ->with('status', 'Instructor permanently deleted.');
    }
}
