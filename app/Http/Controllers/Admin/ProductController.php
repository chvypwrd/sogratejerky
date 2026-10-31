<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Services\HtmlSanitizer;
use App\Services\PageSlugRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private HtmlSanitizer $sanitizer,
        private PageSlugRedirectService $slugService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $filter = $request->get('filter', 'all');

        $query = Product::with(['categories', 'availableVariants'])->withCount(['variants', 'availableVariants'])->ordered();

        $query = match ($filter) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'featured' => $query->where('is_featured', true),
            default => $query,
        };

        $products = $query->paginate(20)->withQueryString();

        return view('admin.products.index', compact('products', 'filter'));
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.create', [
            'product' => new Product([
                'is_active' => true,
                'is_featured' => false,
            ]),
            'categories' => ProductCategory::ordered()->get(),
            'tags' => ProductTag::ordered()->get(),
            'alt_text' => null,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $this->prepareData($request->validated());
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        unset($data['categories'], $data['tags'], $data['primary_image'], $data['gallery_images'], $data['alt_text']);

        $product = Product::create($data);
        $product->categories()->sync($request->input('categories', []));
        $product->tags()->sync($request->input('tags', []));

        $this->savePrimaryImage($product, $request);
        $this->saveGalleryImages($product, $request);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load('categories', 'tags', 'variants');

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => ProductCategory::ordered()->get(),
            'tags' => ProductTag::ordered()->get(),
            'alt_text' => $product->getFirstMedia('primary_image')?->getCustomProperty('alt_text', ''),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $oldSlug = $product->slug;
        $data = $this->prepareData($request->validated());
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        unset(
            $data['categories'],
            $data['tags'],
            $data['primary_image'],
            $data['gallery_images'],
            $data['alt_text'],
            $data['remove_primary_image'],
            $data['remove_gallery_images']
        );

        $product->update($data);
        $this->slugService->handleSlugChange($product, $oldSlug, 'shop/');

        $product->categories()->sync($request->input('categories', []));
        $product->tags()->sync($request->input('tags', []));

        if ($request->boolean('remove_primary_image')) {
            $product->clearMediaCollection('primary_image');
        } else {
            $this->savePrimaryImage($product, $request);
        }

        if ($request->boolean('remove_gallery_images')) {
            $product->clearMediaCollection('gallery');
        }

        $this->saveGalleryImages($product, $request);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product deleted successfully.');
    }

    private function prepareData(array $data): array
    {
        $data['description'] = $this->sanitizer->clean($data['description'] ?? null);
        $data['short_description'] = isset($data['short_description']) ? trim(strip_tags($data['short_description'])) : null;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function savePrimaryImage(Product $product, Request $request): void
    {
        if (! $request->hasFile('primary_image')) {
            if ($product->hasMedia('primary_image')) {
                $media = $product->getFirstMedia('primary_image');
                $media->setCustomProperty('alt_text', $request->input('alt_text', ''));
                $media->save();
            }

            return;
        }

        $product->clearMediaCollection('primary_image');
        $product->addMediaFromRequest('primary_image')
            ->withCustomProperties(['alt_text' => $request->input('alt_text', '')])
            ->toMediaCollection('primary_image');
    }

    private function saveGalleryImages(Product $product, Request $request): void
    {
        if (! $request->hasFile('gallery_images')) {
            return;
        }

        foreach ($request->file('gallery_images') as $file) {
            $product->addMedia($file)
                ->usingName($product->name)
                ->toMediaCollection('gallery');
        }
    }
}
