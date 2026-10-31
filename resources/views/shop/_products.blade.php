@if($products->count() > 0)
    <div class="row g-4">
        @foreach($products as $product)
            <div class="col-sm-6 col-lg-4">
                @include('shop._product-card', ['product' => $product])
            </div>
        @endforeach
    </div>

    <div class="mt-5">
        {{ $products->links() }}
    </div>
@else
    <div class="empty-state">
        <p class="mb-0">No products available right now. Check back soon.</p>
    </div>
@endif
