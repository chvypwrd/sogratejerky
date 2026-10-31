@extends('layouts.app')

@section('title', 'Shop Jerky')

@section('meta')
    <link rel="canonical" href="{{ route('shop.index') }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e(config('app.name')) }} - Shop Jerky">
    <meta property="og:url" content="{{ route('shop.index') }}">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Shop</span>
            <h1>Shop Jerky</h1>
            <p class="lead text-muted">Small-batch SOGRATE beef jerky, made in Texas.</p>
        </header>

        @include('shop._filters', ['sort' => $sort, 'categories' => $categories, 'tags' => $tags])

        @include('shop._products', ['products' => $products])
    </div>
</section>
@endsection
