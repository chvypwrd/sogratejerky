<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    public function create(Product $product): View
    {
        $this->authorize('update', $product);

        return view('admin.products.variants.create', [
            'product' => $product,
            'variant' => new ProductVariant([
                'is_active' => true,
                'sort_order' => $product->variants()->max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $this->prepareData($request->validated());
        $data['product_id'] = $product->id;
        $data['is_active'] = $request->boolean('is_active');

        unset($data['image']);

        $variant = ProductVariant::create($data);
        $this->saveImage($variant, $request);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Variant created successfully.');
    }

    public function edit(Product $product, ProductVariant $variant): View
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->id, 404);

        return view('admin.products.variants.edit', compact('product', 'variant'));
    }

    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->id, 404);

        $data = $this->prepareData($request->validated());
        $data['is_active'] = $request->boolean('is_active');

        unset($data['image'], $data['remove_image']);

        $variant->update($data);

        if ($request->boolean('remove_image')) {
            $variant->clearMediaCollection('image');
        } else {
            $this->saveImage($variant, $request);
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Variant updated successfully.');
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->id, 404);

        $variant->delete();

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Variant deleted successfully.');
    }

    private function prepareData(array $data): array
    {
        $data['price'] = (int) round(((float) $data['price']) * 100);
        $data['weight'] = (int) $data['weight'];
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function saveImage(ProductVariant $variant, Request $request): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $variant->clearMediaCollection('image');
        $variant->addMediaFromRequest('image')
            ->usingName($variant->name)
            ->toMediaCollection('image');
    }
}
