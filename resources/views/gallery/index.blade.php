@extends('layouts.app')

@section('title', 'Gallery')

@section('meta')
    <link rel="canonical" href="{{ route('gallery.index', [], false) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Gallery | {{ config('app.name', 'SOGRATE') }}">
    <meta property="og:url" content="{{ route('gallery.index', [], false) }}">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="mb-5">
            <h1>Gallery</h1>
            <p class="lead text-muted">Browse our collection of photos from classes, events, and more.</p>
        </header>

        @if($galleries->count() > 0)
            <div class="row g-4">
                @foreach($galleries as $gallery)
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="{{ route('gallery.show', $gallery->slug) }}" class="text-decoration-none">
                            <div class="card h-100">
                                @php
                                    $coverImage = $gallery->getCoverImage();
                                @endphp
                                @if($coverImage)
                                    <img src="{{ $coverImage->getUrl('thumb') }}"
                                         alt="{{ e($coverImage->getCustomProperty('alt_text', $gallery->title)) }}"
                                         class="card-img-top"
                                         loading="lazy"
                                         style="height: 200px; object-fit: cover;">
                                @else
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <span class="text-muted">No images</span>
                                    </div>
                                @endif

                                <div class="card-body">
                                    <h5 class="card-title text-dark">{{ $gallery->title }}</h5>

                                    @if($gallery->description)
                                        <p class="card-text text-muted">{{ Str::limit(strip_tags($gallery->description), 120) }}</p>
                                    @endif

                                    <span class="badge bg-secondary">{{ $gallery->photoCount() }} {{ Str::plural('photo', $gallery->photoCount()) }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <p class="text-muted">No galleries available at this time. Please check back soon!</p>
            </div>
        @endif
    </div>
</section>
@endsection
