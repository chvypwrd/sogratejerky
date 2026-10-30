@extends('layouts.app')

@section('title', $gallery->meta_title ?: $gallery->title)

@section('meta')
    @if($gallery->meta_description)
        <meta name="description" content="{{ e($gallery->meta_description) }}">
    @endif
    @if($gallery->robots)
        <meta name="robots" content="{{ e($gallery->robots->value) }}">
    @endif

    @php
        $canonicalUrl = $gallery->canonical_url ?: route('gallery.show', $gallery->slug, false);
    @endphp

    <link rel="canonical" href="{{ e($canonicalUrl) }}">

    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ e($gallery->meta_title ?: $gallery->title) }}">
    @if($gallery->meta_description)
        <meta property="og:description" content="{{ e($gallery->meta_description) }}">
    @endif
    <meta property="og:url" content="{{ e($canonicalUrl) }}">
    @php
        $coverImage = $gallery->getCoverImage();
    @endphp
    @if($coverImage)
        <meta property="og:image" content="{{ e($coverImage->getFullUrl()) }}">
    @endif
@endsection

@section('content')
<article class="py-5">
    <div class="container">
        <header class="page-header">
            <a href="{{ route('gallery.index') }}" class="text-muted small mb-4 d-inline-block">&larr; Back to Gallery</a>
            <span class="eyebrow">Gallery</span>
            <h1>{{ $gallery->title }}</h1>
            @if($gallery->description)
                <p class="lead text-muted">{{ $gallery->description }}</p>
            @endif
        </header>

        @php
            $photos = $gallery->getMedia('photos');
        @endphp

        @if($photos->count() > 0)
            <div class="gallery-grid" id="gallery-grid">
                @foreach($photos as $index => $photo)
                    @php
                        $altText = $photo->getCustomProperty('alt_text', $gallery->title);
                        $caption = $photo->getCustomProperty('caption', '');
                        $thumbUrl = $photo->getUrl('thumb');
                        $fullUrl = $photo->getUrl('gallery');
                    @endphp
                    <figure class="gallery-figure" data-index="{{ $index }}">
                        <a href="{{ $fullUrl }}" class="gallery-link" data-gallery-index="{{ $index }}">
                            <img src="{{ $thumbUrl }}"
                                 alt="{{ e($altText) }}"
                                 @if($index > 0) loading="lazy" @endif
                                 class="gallery-image">
                        </a>
                        @if($caption)
                            <figcaption class="gallery-caption">{{ $caption }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <p class="text-muted">No photos in this gallery yet.</p>
            </div>
        @endif
    </div>
</article>
@endsection

@section('structured_data')
    <x-structured-data :data="app(\App\Services\StructuredDataService::class)->galleryBreadcrumbs($gallery->title)" />
@endsection

@section('scripts')
    <script>
        window.__galleryData = {
            images: {!! json_encode($photos->map(fn ($photo) => [
                'thumb' => $photo->getUrl('thumb'),
                'full' => $photo->getUrl('gallery'),
                'alt' => $photo->getCustomProperty('alt_text', $gallery->title),
                'caption' => $photo->getCustomProperty('caption', ''),
            ])->values()) !!},
        };
    </script>
@endsection
