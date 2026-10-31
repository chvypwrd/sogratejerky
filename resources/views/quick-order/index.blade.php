@extends('layouts.app')

@section('title', 'Quick Order')

@section('meta')
    <link rel="canonical" href="{{ route('quick-order.index') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e(config('app.name')) }} - Quick Order">
    <meta property="og:url" content="{{ route('quick-order.index') }}">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Quick Order</span>
            <h1>Quick Order</h1>
            <p class="lead text-muted">Know what you want? Enter quantities and add everything to your cart at once.</p>
        </header>

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        @if($errors->has('items'))
            <div class="alert alert-danger" role="alert">{{ $errors->first('items') }}</div>
        @endif

        @if($products->isEmpty())
            <div class="empty-state">
                <p class="mb-3">No products are available for Quick Order right now.</p>
                <a href="{{ route('shop.index') }}" class="btn btn-brand">Visit Shop</a>
            </div>
        @else
            <form action="{{ route('quick-order.store') }}" method="POST" class="quick-order-form">
                @csrf

                <div class="quick-order-groups">
                    @foreach($products as $product)
                        <section class="quick-order-group">
                            <header class="quick-order-group__header">
                                <h2>{{ $product->name }}</h2>
                                @if($product->short_description)
                                    <p>{{ $product->short_description }}</p>
                                @endif
                            </header>

                            <div class="table-responsive">
                                <table class="table align-middle quick-order-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Variant</th>
                                            <th scope="col">SKU</th>
                                            <th scope="col">Weight</th>
                                            <th scope="col">Price</th>
                                            <th scope="col" class="quick-order-table__qty">Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->availableVariants as $variant)
                                            <tr>
                                                <td>{{ $variant->name }}</td>
                                                <td>{{ $variant->sku }}</td>
                                                <td>{{ $variant->weight_display }}</td>
                                                <td>{{ $variant->price_formatted }}</td>
                                                <td class="quick-order-table__qty">
                                                    <label for="variant-{{ $variant->id }}" class="visually-hidden">Quantity for {{ $product->name }} {{ $variant->name }}</label>
                                                    <input type="number" class="form-control form-control-sm" id="variant-{{ $variant->id }}" name="items[{{ $variant->id }}]" value="{{ old('items.'.$variant->id, 0) }}" min="0" max="{{ config('cart.max_quantity', 99) }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endforeach
                </div>

                <div class="quick-order-actions">
                    <button type="submit" class="btn btn-brand btn-lg">Add Selected Items to Cart</button>
                    <a href="{{ route('shop.index') }}" class="btn btn-brand-outline">Back to Shop</a>
                </div>
            </form>
        @endif
    </div>
</section>
@endsection
