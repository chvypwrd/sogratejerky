@extends('layouts.app')

@section('title', $class->meta_title ?: $class->title)

@section('meta')
    <x-seo-meta
        :title="$class->meta_title ?: $class->title"
        :description="$class->meta_description"
        :robots="$class->robots->value"
        :canonicalUrl="$class->canonical_url ?: route('classes.show', $class->slug, false)"
        :ogImage="$class->getFirstMedia('featured')?->getFullUrl()"
    />
@endsection

@section('content')
<article class="py-5">
    <div class="container">
        <header class="mb-5">
            <h1>{{ $class->title }}</h1>
            <div class="d-flex gap-2 mb-3">
                @if($class->level)
                    <span class="badge bg-secondary">{{ $class->level }}</span>
                @endif
                @if($class->duration)
                    <span class="badge bg-light text-dark">{{ $class->duration }}</span>
                @endif
            </div>
            @if($class->short_description)
                <p class="lead text-muted">{{ $class->short_description }}</p>
            @endif
        </header>

        @if($class->hasMedia('featured'))
            @php
                $media = $class->getFirstMedia('featured');
                $altText = $media->getCustomProperty('alt_text', $class->title);
            @endphp
            <div class="mb-4">
                <img src="{{ $media->getFullUrl() }}"
                     alt="{{ e($altText) }}"
                     class="img-fluid rounded">
            </div>
        @endif

        @if($class->description)
            <div class="page-content">
                {!! $class->description !!}
            </div>
        @endif
    </div>
</article>
@endsection

@section('structured_data')
    <x-structured-data :data="app(\App\Services\StructuredDataService::class)->breadcrumbs('Classes', $class->title)" />
@endsection
