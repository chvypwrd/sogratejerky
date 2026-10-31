@php
    $image = $product->getPrimaryImage();
    $imageUrl = $image?->getUrl('card');
    $altText = $image?->getCustomProperty('alt_text', $product->name) ?: $product->name;
    $summary = $product->short_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 130);
@endphp

<article class="product-card card h-100">
    <a href="{{ route('shop.show', ['product' => $product->slug]) }}" class="product-card__media">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ e($altText) }}" loading="lazy">
        @else
            <span class="product-card__placeholder">{{ config('app.name', 'SOGRATE') }}</span>
        @endif
    </a>

    <div class="card-body">
        @if($product->categories->isNotEmpty())
            <div class="product-categories">
                @foreach($product->categories as $category)
                    <a href="{{ route('shop.category', $category->slug) }}">{{ $category->name }}</a>
                @endforeach
            </div>
        @endif

        <h2 class="product-card__title">
            <a href="{{ route('shop.show', ['product' => $product->slug]) }}">{{ $product->name }}</a>
        </h2>

        @if($summary)
            <p class="card-text">{{ $summary }}</p>
        @endif

        <div class="product-card__footer">
            <span class="product-price">{{ $product->starting_price_formatted ? 'From '.$product->starting_price_formatted : 'Unavailable' }}</span>
            <a href="{{ route('shop.show', ['product' => $product->slug]) }}" class="product-card__link">View Product</a>
        </div>
    </div>
</article>
