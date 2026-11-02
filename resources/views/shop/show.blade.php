@extends('layouts.app')

@section('title', $product->seo_title ?: $product->name)
@section('meta_description', $product->seoDescriptionText())

@section('meta')
    @php
        $seoDescription = $product->seoDescriptionText();
        $canonicalUrl = route('shop.show', ['product' => $product->slug]);
        $featuredMedia = $product->getPrimaryImage();
    @endphp

    @if($variants->isEmpty())
        <meta name="robots" content="noindex,follow">
    @endif
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ e($product->seo_title ?: $product->name) }}">
    <meta property="og:description" content="{{ e($seoDescription) }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if($featuredMedia)
        <meta property="og:image" content="{{ $featuredMedia->getUrl('card') }}">
    @endif
@endsection

@php
    $mainMedia = $selectedVariant?->getFirstMedia('image') ?? $product->getPrimaryImage();
    $mainImageUrl = $mainMedia?->getUrl('card');
    $mainAlt = $mainMedia?->getCustomProperty('alt_text', $selectedVariant?->name ?: $product->name) ?: $product->name;
    $galleryImages = $product->getMedia('gallery');
@endphp

@section('content')
<article class="py-5">
    <div class="container">
        <a href="{{ route('shop.index') }}" class="text-muted small mb-4 d-inline-block">&larr; Back to Shop</a>

        <div class="row g-4 g-lg-5">
            <div class="col-lg-7">
                <div class="product-gallery">
                    <div class="product-gallery__main">
                        @if($mainImageUrl)
                            <img id="product-main-image" src="{{ $mainImageUrl }}" alt="{{ e($mainAlt) }}" width="800" height="600" decoding="async">
                        @else
                            <div class="product-gallery__placeholder" id="product-main-image">{{ config('app.name', 'Sograte') }}</div>
                        @endif
                    </div>

                    @if($galleryImages->count() > 0)
                        <div class="product-gallery__thumbs" aria-label="Product images">
                            @foreach($galleryImages as $media)
                                <button type="button" class="product-gallery__thumb" data-gallery-image="{{ $media->getUrl('card') }}" aria-label="View {{ $product->name }} image {{ $loop->iteration }}">
                                    <img src="{{ $media->getUrl('thumb') }}" alt="{{ e($media->getCustomProperty('alt_text', $product->name)) }}" width="400" height="300" loading="lazy" decoding="async">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-5">
                <header class="product-detail-header">
                    <span class="eyebrow">Sograte Jerky</span>
                    <h1>{{ $product->name }}</h1>
                    @if($product->short_description)
                        <p class="lead text-muted">{{ $product->short_description }}</p>
                    @endif

                    @if($product->categories->isNotEmpty())
                        <div class="product-categories">
                            @foreach($product->categories as $category)
                                <a href="{{ route('shop.category', $category->slug) }}">{{ $category->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </header>

                @if(session('status'))
                    <div class="alert alert-success" role="status">
                        {{ session('status') }}
                        <a href="{{ route('cart.index') }}" class="alert-link">View Cart</a>
                    </div>
                @endif

                @if($variants->count() > 0)
                    <form action="{{ route('cart.items.store') }}" method="POST" class="product-purchase-info">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <div class="product-price product-price--large" id="product-price">{{ $selectedVariant?->price_formatted }}</div>

                        <div class="mb-3">
                            <label for="variant-select" class="form-label">Choose a size</label>
                            <select class="form-select @error('product_variant_id') is-invalid @enderror" id="variant-select" name="product_variant_id">
                                @foreach($variants as $variant)
                                    <option value="{{ $variant->id }}" {{ $selectedVariant?->id === $variant->id ? 'selected' : '' }}>
                                        {{ $variant->name }} - {{ $variant->price_formatted }} - {{ $variant->weight_display }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_variant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <dl class="product-details-list">
                            <div>
                                <dt>Weight</dt>
                                <dd id="product-weight">{{ $selectedVariant?->weight_display }}</dd>
                            </div>
                            <div>
                                <dt>SKU</dt>
                                <dd id="product-sku">{{ $selectedVariant?->sku }}</dd>
                            </div>
                            <div>
                                <dt>Availability</dt>
                                <dd id="product-availability">Available</dd>
                            </div>
                        </dl>

                        <div class="row g-3 align-items-end">
                            <div class="col-4">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="{{ config('cart.max_quantity', 99) }}" required>
                                @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-8">
                                <button type="submit" class="btn btn-brand btn-lg w-100">Add to Cart</button>
                            </div>
                        </div>

                        <div class="product-cart-links">
                            <a href="{{ route('cart.index') }}">View Cart</a>
                            <a href="{{ route('quick-order.index') }}">Quick Order</a>
                        </div>
                    </form>
                @else
                    <div class="alert alert-warning">
                        This product is currently unavailable. Contact us for availability.
                    </div>
                @endif

                @if($product->tags->isNotEmpty())
                    <div class="product-tags">
                        <span class="product-tags__label">Tags:</span>
                        @foreach($product->tags as $tag)
                            <a href="{{ route('shop.tag', $tag->slug) }}">{{ $tag->name }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if($product->description)
            <div class="row mt-5">
                <div class="col-lg-9">
                    <div class="rich-content product-description">
                        {!! $product->description !!}
                    </div>
                </div>
            </div>
        @endif
    </div>
</article>
@endsection

@section('structured_data')
    <x-structured-data :data="app(\App\Services\StructuredDataService::class)->product($product, $variants)" />
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const variants = @json($variantData);
    const select = document.getElementById('variant-select');
    const mainImage = document.getElementById('product-main-image');

    function updateVariant(id) {
        const data = variants[id];
        if (!data) {
            return;
        }

        document.getElementById('product-price').textContent = data.price;
        document.getElementById('product-weight').textContent = data.weight;
        document.getElementById('product-sku').textContent = data.sku;

        if (mainImage && data.image) {
            if (mainImage.tagName === 'IMG') {
                mainImage.src = data.image;
                mainImage.alt = data.name;
            }
        }

        const url = new URL(window.location.href);
        url.searchParams.set('variant', id);
        window.history.replaceState({}, '', url.toString());
    }

    if (select) {
        select.addEventListener('change', function () {
            updateVariant(this.value);
        });

        if (select.value) {
            const url = new URL(window.location.href);
            if (url.searchParams.get('variant') !== select.value) {
                url.searchParams.set('variant', select.value);
                window.history.replaceState({}, '', url.toString());
            }
        }
    }

    document.querySelectorAll('[data-gallery-image]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (mainImage && mainImage.tagName === 'IMG') {
                mainImage.src = this.dataset.galleryImage;
            }
        });
    });
});
</script>
@endsection
