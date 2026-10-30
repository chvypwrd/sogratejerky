<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Models\Faq;
use App\Services\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __construct(
        private HtmlSanitizer $sanitizer,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Faq::class);

        $filter = $request->get('filter', 'all');

        $query = Faq::with('creator');

        $query = match ($filter) {
            'active' => $query->where('active', true),
            'inactive' => $query->where('active', false),
            'trash' => Faq::onlyTrashed()->with('creator'),
            default => $query,
        };

        $faqs = $query->ordered()->paginate(20)->withQueryString();

        return view('admin.faqs.index', compact('faqs', 'filter'));
    }

    public function create(): View
    {
        $this->authorize('create', Faq::class);

        return view('admin.faqs.create', [
            'faq' => new Faq,
        ]);
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        $this->authorize('create', Faq::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['answer'] = $this->sanitizer->clean($data['answer'] ?? null);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $faq = Faq::create($data);

        return redirect()
            ->route('admin.faqs.edit', $faq)
            ->with('status', 'FAQ created successfully.');
    }

    public function edit(Faq $faq): View
    {
        $this->authorize('update', $faq);

        return view('admin.faqs.edit', [
            'faq' => $faq,
        ]);
    }

    public function update(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        $this->authorize('update', $faq);

        $data = $request->validated();
        $data['answer'] = $this->sanitizer->clean($data['answer'] ?? null);
        $data['updated_by'] = $request->user()->id;

        $faq->update($data);

        return redirect()
            ->route('admin.faqs.edit', $faq)
            ->with('status', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $this->authorize('delete', $faq);

        $faq->delete();

        return redirect()
            ->route('admin.faqs.index')
            ->with('status', 'FAQ moved to trash.');
    }

    public function restore(Faq $faq): RedirectResponse
    {
        $this->authorize('delete', $faq);

        $faq->restore();

        return redirect()->back()->with('status', 'FAQ restored successfully.');
    }

    public function forceDelete(Faq $faq): RedirectResponse
    {
        $this->authorize('delete', $faq);

        $faq->forceDelete();

        return redirect()
            ->route('admin.faqs.index')
            ->with('status', 'FAQ permanently deleted.');
    }
}
