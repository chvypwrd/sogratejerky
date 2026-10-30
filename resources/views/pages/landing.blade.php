@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title)

@section('meta')
    @if($page->meta_description)
        <meta name="description" content="{{ e($page->meta_description) }}">
    @endif
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
<article>
    {{-- Landing pages get a larger, more impactful header --}}
    @if($page->hasMedia('featured'))
        @php
            $media = $page->getFirstMedia('featured');
            $altText = $media->getCustomProperty('alt_text', $page->title);
        @endphp
        <div class="landing-hero" style="background-image: url('{{ $media->getFullUrl() }}'); background-size: cover; background-position: center; min-height: 50vh; display: flex; align-items: flex-end;">
            <div class="container py-5">
                <div class="row">
                    <div class="col-lg-8">
                        <h1 class="display-4 fw-bold text-white" style="text-shadow: 0 2px 8px rgba(0,0,0,0.5);">{{ $page->title }}</h1>
                        @if($page->excerpt)
                            <p class="lead text-white" style="text-shadow: 0 1px 4px rgba(0,0,0,0.4);">{{ $page->excerpt }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="landing-hero bg-dark text-white py-5">
            <div class="container py-5">
                <div class="row">
                    <div class="col-lg-8">
                        <h1 class="display-4 fw-bold">{{ $page->title }}</h1>
                        @if($page->excerpt)
                            <p class="lead">{{ $page->excerpt }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="container py-5">
        <div class="rich-content" style="max-width: 100%;">
            {!! $page->body !!}
        </div>
    </div>
</article>
@endsection
