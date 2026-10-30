<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRedirectRequest;
use App\Http\Requests\Admin\UpdateRedirectRequest;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RedirectController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Redirect::class);

        $filter = $request->get('filter', 'all');

        $query = Redirect::query();

        $query = match ($filter) {
            'active' => $query->where('active', true),
            'inactive' => $query->where('active', false),
            default => $query,
        };

        $redirects = $query->latest()->paginate(20)->withQueryString();

        return view('admin.redirects.index', compact('redirects', 'filter'));
    }

    public function create(): View
    {
        $this->authorize('create', Redirect::class);

        return view('admin.redirects.create', [
            'redirect' => new Redirect,
        ]);
    }

    public function store(StoreRedirectRequest $request): RedirectResponse
    {
        $this->authorize('create', Redirect::class);

        Redirect::create($request->validated());

        return redirect()
            ->route('admin.redirects.index')
            ->with('status', 'Redirect created successfully.');
    }

    public function edit(Redirect $redirect): View
    {
        $this->authorize('update', $redirect);

        return view('admin.redirects.edit', compact('redirect'));
    }

    public function update(UpdateRedirectRequest $request, Redirect $redirect): RedirectResponse
    {
        $this->authorize('update', $redirect);

        $redirect->update($request->validated());

        return redirect()
            ->route('admin.redirects.index')
            ->with('status', 'Redirect updated successfully.');
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        $this->authorize('delete', $redirect);

        $redirect->delete();

        return redirect()
            ->route('admin.redirects.index')
            ->with('status', 'Redirect deleted successfully.');
    }
}
