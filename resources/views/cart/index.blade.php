@extends('layouts.app')

@section('title', 'Your Cart')

@section('meta')
    <meta name="robots" content="noindex,follow">
    <link rel="canonical" href="{{ route('cart.index') }}">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Cart</span>
            <h1>Your Cart</h1>
            <p class="lead text-muted">Review your SOGRATE jerky selection.</p>
        </header>

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        @if($cart->items->isEmpty())
            <div class="empty-state">
                <p class="mb-3">Your cart is empty.</p>
                <a href="{{ route('shop.index') }}" class="btn btn-brand">Shop Jerky</a>
            </div>
        @else
            <div class="row g-5">
                <div class="col-lg-8">
                    <div class="cart-lines">
                        @foreach($cart->items as $item)
                            @php
                                $variant = $item->variant;
                                $product = $variant?->product;
                                $available = $item->isAvailable();
                                $image = $variant?->getFirstMedia('image') ?? $product?->getPrimaryImage();
                            @endphp
                            <article class="cart-line {{ $available ? '' : 'cart-line--unavailable' }}">
                                <div class="cart-line__media">
                                    @if($image)
                                        <img src="{{ $image->getUrl('thumb') }}" alt="{{ e($product?->name ?? 'Product') }}">
                                    @else
                                        <span>{{ config('app.name', 'SOGRATE') }}</span>
                                    @endif
                                </div>

                                <div class="cart-line__info">
                                    <h2>
                                        @if($product)
                                            <a href="{{ route('shop.show', ['product' => $product->slug]) }}">{{ $product->name }}</a>
                                        @else
                                            Unavailable product
                                        @endif
                                    </h2>
                                    <p class="mb-1">{{ $variant?->name ?? 'Unavailable variant' }}</p>
                                    @if($variant)
                                        <p class="cart-line__meta">SKU: {{ $variant->sku }} &middot; {{ $variant->weight_display }}</p>
                                        <p class="cart-line__meta">Unit price: {{ $variant->price_formatted }}</p>
                                    @endif
                                    @if(! $available)
                                        <p class="cart-line__warning">This item is no longer available and is not included in the subtotal.</p>
                                    @endif
                                </div>

                                <div class="cart-line__quantity">
                                    <form action="{{ route('cart.items.update', $item) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <label for="quantity-{{ $item->id }}" class="form-label">Quantity</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" id="quantity-{{ $item->id }}" name="quantity" value="{{ $item->quantity }}" min="0" max="{{ config('cart.max_quantity', 99) }}">
                                            <button type="submit" class="btn btn-outline-secondary">Update</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="cart-line__subtotal">
                                    <span class="cart-line__label">Line total</span>
                                    <strong>{{ $available ? $item->line_subtotal_formatted : 'Unavailable' }}</strong>
                                </div>

                                <div class="cart-line__remove">
                                    <form action="{{ route('cart.items.destroy', $item) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <form action="{{ route('cart.clear') }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Clear your entire cart?')">Clear Cart</button>
                    </form>
                </div>

                <div class="col-lg-4">
                    <aside class="cart-summary">
                        <h2 class="h4">Order Summary</h2>
                        <div class="cart-summary__row">
                            <span>Items</span>
                            <strong>{{ $itemCount }}</strong>
                        </div>
                        <div class="cart-summary__row cart-summary__total">
                            <span>Subtotal</span>
                            <strong>${{ number_format($subtotal / 100, 2) }}</strong>
                        </div>
                        <p class="cart-summary__note">Available items only. Taxes and shipping are calculated later.</p>

                        <div class="cart-summary__checkout">
                            <a href="{{ route('checkout.index') }}" class="btn btn-brand w-100">Proceed to Checkout</a>
                            <p class="mb-0 mt-2">Fulfillment options and taxes are confirmed at checkout.</p>
                        </div>

                        <a href="{{ route('shop.index') }}" class="btn btn-brand-outline w-100 mt-3">Continue Shopping</a>
                    </aside>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
