<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTestimonialRequest;
use App\Http\Requests\Admin\UpdateTestimonialRequest;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Testimonial::class);

        $filter = $request->get('filter', 'all');

        $query = Testimonial::with('creator');

        $query = match ($filter) {
            'active' => $query->where('active', true),
            'inactive' => $query->where('active', false),
            'trash' => Testimonial::onlyTrashed()->with('creator'),
            default => $query,
        };

        $testimonials = $query->ordered()->paginate(20)->withQueryString();

        return view('admin.testimonials.index', compact('testimonials', 'filter'));
    }

    public function create(): View
    {
        $this->authorize('create', Testimonial::class);

        return view('admin.testimonials.create', [
            'testimonial' => new Testimonial,
        ]);
    }

    public function store(StoreTestimonialRequest $request): RedirectResponse
    {
        $this->authorize('create', Testimonial::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $testimonial = Testimonial::create($data);

        return redirect()
            ->route('admin.testimonials.edit', $testimonial)
            ->with('status', 'Testimonial created successfully.');
    }

    public function edit(Testimonial $testimonial): View
    {
        $this->authorize('update', $testimonial);

        return view('admin.testimonials.edit', [
            'testimonial' => $testimonial,
        ]);
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $testimonial->update($data);

        return redirect()
            ->route('admin.testimonials.edit', $testimonial)
            ->with('status', 'Testimonial updated successfully.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('delete', $testimonial);

        $testimonial->delete();

        return redirect()
            ->route('admin.testimonials.index')
            ->with('status', 'Testimonial moved to trash.');
    }

    public function restore(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('delete', $testimonial);

        $testimonial->restore();

        return redirect()->back()->with('status', 'Testimonial restored successfully.');
    }

    public function forceDelete(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('delete', $testimonial);

        $testimonial->forceDelete();

        return redirect()
            ->route('admin.testimonials.index')
            ->with('status', 'Testimonial permanently deleted.');
    }
}
