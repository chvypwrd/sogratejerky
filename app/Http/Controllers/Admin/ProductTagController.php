<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductTagRequest;
use App\Http\Requests\Admin\UpdateProductTagRequest;
use App\Models\ProductTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductTagController extends Controller
{
    public function index(): View
    {
        $tags = ProductTag::withCount('products')->ordered()->paginate(20);

        return view('admin.products.tags.index', compact('tags'));
    }

    public function create(): View
    {
        return view('admin.products.tags.create', ['tag' => new ProductTag]);
    }

    public function store(StoreProductTagRequest $request): RedirectResponse
    {
        ProductTag::create($request->validated());

        return redirect()
            ->route('admin.product-tags.index')
            ->with('status', 'Product tag created successfully.');
    }

    public function edit(ProductTag $productTag): View
    {
        return view('admin.products.tags.edit', ['tag' => $productTag]);
    }

    public function update(UpdateProductTagRequest $request, ProductTag $productTag): RedirectResponse
    {
        $productTag->update($request->validated());

        return redirect()
            ->route('admin.product-tags.index')
            ->with('status', 'Product tag updated successfully.');
    }

    public function destroy(ProductTag $productTag): RedirectResponse
    {
        if ($productTag->products()->exists()) {
            return redirect()
                ->route('admin.product-tags.index')
                ->with('error', 'Remove this tag from its products before deleting it.');
        }

        $productTag->delete();

        return redirect()
            ->route('admin.product-tags.index')
            ->with('status', 'Product tag deleted successfully.');
    }
}
