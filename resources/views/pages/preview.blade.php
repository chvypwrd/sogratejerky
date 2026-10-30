@extends('layouts.app')

@section('title', 'Preview: ' . $page->title)

@section('meta')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
<article class="py-5">
    <div class="container">
        <div class="alert alert-warning mb-4">
            <strong>Preview Mode</strong> — This is a preview of the page. It is not publicly visible.
        </div>

        <header class="mb-5">
            <h1>{{ $page->title }}</h1>
            @if($page->excerpt)
                <p class="lead text-muted">{{ $page->excerpt }}</p>
            @endif
            <div class="text-muted small">
                Status: <span class="badge bg-{{ $page->status->color() }}">{{ $page->status->label() }}</span>
            </div>
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

        <div class="page-content">
            {!! $page->body !!}
        </div>
    </div>
</article>
@endsection
