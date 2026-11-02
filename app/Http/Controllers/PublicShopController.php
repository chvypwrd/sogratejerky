<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\ProductVariant;
use App\Services\AnalyticsService;
use App\Services\PageSlugRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicShopController extends Controller
{
    public function __construct(
        private AnalyticsService $analytics,
    ) {}

    public function index(Request $request): View
    {
        $products = $this->productQuery($request)->paginate(12)->withQueryString();

        return view('shop.index', [
            'products' => $products,
            'categories' => ProductCategory::active()->ordered()->get(),
            'tags' => ProductTag::ordered()->get(),
            'sort' => $request->get('sort', 'featured'),
        ]);
    }

    public function category(ProductCategory $productCategory): View
    {
        abort_unless($productCategory->is_active, 404);

        $products = $productCategory->products()
            ->catalog()
            ->with(['media', 'availableVariants.media', 'availableVariants', 'categories'])
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        return view('shop.archive', [
            'products' => $products,
            'heading' => $productCategory->name,
            'description' => $productCategory->description,
            'archiveType' => 'Category',
            'seoTitle' => $productCategory->seo_title ?: $productCategory->name,
            'seoDescription' => $productCategory->seo_description ?: $productCategory->description ?: 'Browse '.$productCategory->name.' from Sograte, Texas-made beef jerky.',
            'categories' => ProductCategory::active()->ordered()->get(),
            'tags' => ProductTag::ordered()->get(),
        ]);
    }

    public function tag(ProductTag $productTag): View
    {
        $products = $productTag->products()
            ->catalog()
            ->with(['media', 'availableVariants.media', 'availableVariants', 'categories'])
            ->ordered()
            ->paginate(12)
            ->withQueryString();

        return view('shop.archive', [
            'products' => $products,
            'heading' => $productTag->name,
            'description' => null,
            'archiveType' => 'Tag',
            'seoTitle' => 'Tag: '.$productTag->name,
            'seoDescription' => 'Browse Sograte beef jerky tagged '.$productTag->name.'.',
            'categories' => ProductCategory::active()->ordered()->get(),
            'tags' => ProductTag::ordered()->get(),
        ]);
    }

    public function show(Request $request, string $product, PageSlugRedirectService $slugService): View|RedirectResponse
    {
        $redirect = $slugService->resolve('shop/'.$product);

        if ($redirect !== null) {
            return redirect($redirect['to'], $redirect['status']);
        }

        $product = Product::active()
            ->with(['media', 'availableVariants.media', 'availableVariants', 'categories', 'tags'])
            ->where('slug', $product)
            ->firstOrFail();

        $variants = $product->availableVariants;
        $selectedVariant = $variants->firstWhere('id', $request->integer('variant')) ?? $variants->first();

        if ($selectedVariant) {
            $this->analytics->queue('view_item', [
                'currency' => 'USD',
                'value' => round($selectedVariant->price / 100, 2),
                'items' => [$this->analytics->productItem($product, $selectedVariant)],
            ]);
        }

        $variantData = $variants->mapWithKeys(function (ProductVariant $variant) use ($product) {
            return [
                $variant->id => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'price' => $variant->price_formatted,
                    'weight' => $variant->weight_display,
                    'image' => $variant->getFirstMediaUrl('image', 'card')
                        ?: $product->getPrimaryImage()?->getUrl('card'),
                    'url' => route('shop.show', ['product' => $product->slug, 'variant' => $variant->id]),
                ],
            ];
        });

        return view('shop.show', compact('product', 'variants', 'selectedVariant', 'variantData'));
    }

    private function productQuery(Request $request)
    {
        $query = Product::catalog()->with(['media', 'availableVariants.media', 'availableVariants', 'categories']);

        return match ($request->get('sort', 'featured')) {
            'name' => $query->orderBy('name'),
            'newest' => $query->latest(),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('name'),
        };
    }
}
