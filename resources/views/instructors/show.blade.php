@extends('layouts.app')

@section('title', $instructor->meta_title ?: $instructor->name)

@section('meta')
    <x-seo-meta
        :title="$instructor->meta_title ?: $instructor->name"
        :description="$instructor->meta_description"
        :robots="$instructor->robots->value"
        :canonicalUrl="$instructor->canonical_url ?: route('instructors.show', $instructor->slug, false)"
        :ogImage="$instructor->getFirstMedia('portrait')?->getFullUrl()"
    />
@endsection

@section('content')
<article class="py-5">
    <div class="container">
        <div class="row">
            @if($instructor->hasMedia('portrait'))
                <div class="col-md-4 mb-4">
                    @php
                        $media = $instructor->getFirstMedia('portrait');
                        $altText = $media->getCustomProperty('alt_text', $instructor->name);
                    @endphp
                    <img src="{{ $media->getFullUrl() }}"
                         alt="{{ e($altText) }}"
                         class="img-fluid rounded">
                </div>
            @endif

            <div class="{{ $instructor->hasMedia('portrait') ? 'col-md-8' : 'col-12' }}">
                <header class="mb-4">
                    <h1>{{ $instructor->name }}</h1>
                    @if($instructor->title)
                        <p class="lead text-muted">{{ $instructor->title }}</p>
                    @endif
                </header>

                @if($instructor->bio)
                    <div class="page-content">
                        {!! $instructor->bio !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
</article>
@endsection

@section('structured_data')
    <x-structured-data :data="app(\App\Services\StructuredDataService::class)->breadcrumbs('Instructors', $instructor->name)" />
@endsection
