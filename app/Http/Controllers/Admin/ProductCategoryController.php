<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductCategoryRequest;
use App\Http\Requests\Admin\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ProductCategory::withCount('products')->ordered()->paginate(20);

        return view('admin.products.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.products.categories.create', [
            'category' => new ProductCategory(['is_active' => true]),
        ]);
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        ProductCategory::create($data);

        return redirect()
            ->route('admin.product-categories.index')
            ->with('status', 'Product category created successfully.');
    }

    public function edit(ProductCategory $productCategory): View
    {
        return view('admin.products.categories.edit', ['category' => $productCategory]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $productCategory->update($data);

        return redirect()
            ->route('admin.product-categories.index')
            ->with('status', 'Product category updated successfully.');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        if ($productCategory->products()->exists()) {
            return redirect()
                ->route('admin.product-categories.index')
                ->with('error', 'Remove this category from its products before deleting it.');
        }

        $productCategory->delete();

        return redirect()
            ->route('admin.product-categories.index')
            ->with('status', 'Product category deleted successfully.');
    }
}
