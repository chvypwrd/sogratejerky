@extends('layouts.app')

@section('title', 'Shop Jerky')
@section('meta_description', 'Shop small-batch SOGRATE beef jerky, made in Texas with bold BBQ flavor.')

@section('meta')
    @php
        $canonicalUrl = $products->currentPage() > 1 ? $products->url($products->currentPage()) : route('shop.index');
    @endphp
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e(config('app.name')) }} - Shop Jerky">
    <meta property="og:url" content="{{ $canonicalUrl }}">
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
