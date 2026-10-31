@extends('layouts.app')

@section('title', $seoTitle ?: ($archiveType . ': ' . $heading))

@section('meta')
    @if($seoDescription)
        <meta name="description" content="{{ e($seoDescription) }}">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e(config('app.name')) }} - {{ $heading }}">
    <meta property="og:url" content="{{ url()->current() }}">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Shop {{ $archiveType }}</span>
            <h1>{{ $heading }}</h1>
            @if($description)
                <p class="lead text-muted">{{ $description }}</p>
            @endif
            <a href="{{ route('shop.index') }}" class="small text-muted">&larr; All products</a>
        </header>

        @include('shop._filters', ['sort' => null, 'categories' => $categories, 'tags' => $tags])

        @include('shop._products', ['products' => $products])
    </div>
</section>
@endsection
