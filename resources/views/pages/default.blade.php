@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description)

@section('meta')
    @if($page->robots)
        <meta name="robots" content="{{ e($page->robots->value) }}">
    @endif

    @php
        $canonicalUrl = $page->canonical_url ?: route('pages.show', $page->slug, false);
    @endphp

    <link rel="canonical" href="{{ e($canonicalUrl) }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e($page->meta_title ?: $page->title) }}">
    @if($page->meta_description)
        <meta property="og:description" content="{{ e($page->meta_description) }}">
    @endif
    <meta property="og:url" content="{{ e($canonicalUrl) }}">
    @if($page->hasMedia('featured'))
        <meta property="og:image" content="{{ e($page->getFirstMedia('featured')->getFullUrl()) }}">
    @endif
@endsection

@section('content')
<article class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <a href="{{ route('home') }}" class="text-muted small mb-4 d-inline-block">&larr; Back to home</a>

                <header class="page-header">
                    <span class="eyebrow">SOGRATE</span>
                    <h1>{{ $page->title }}</h1>
                    @if($page->excerpt)
                        <p class="lead text-muted">{{ $page->excerpt }}</p>
                    @endif
                </header>

                @if($page->hasMedia('featured'))
                    @php
                        $media = $page->getFirstMedia('featured');
                        $altText = $media->getCustomProperty('alt_text', $page->title);
                    @endphp
                    <div class="mb-4">
                        <img src="{{ $media->getFullUrl() }}"
                             alt="{{ e($altText) }}"
                             class="img-fluid rounded">
                    </div>
                @endif

                <div class="rich-content">
                    {!! $page->body !!}
                </div>
            </div>
        </div>
    </div>
</article>
@endsection
