@extends('layouts.app')

@section('title', 'Frequently Asked Questions')

@section('meta')
    <link rel="canonical" href="{{ route('faq.index', [], false) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e(config('app.name')) }} - Frequently Asked Questions">
    <meta property="og:url" content="{{ route('faq.index', [], false) }}">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">FAQs</span>
            <h1>Frequently Asked Questions</h1>
            <p class="lead text-muted">Find answers to common questions.</p>
        </header>

        @if($faqs->count() > 0)
            @php
                $uncategorized = $grouped->get('', $grouped->get(null, collect()));
                $categorized = $grouped->filter(fn($items, $key) => $key !== '' && $key !== null);
            @endphp

            @if($categorized->count() > 0)
                @foreach($categorized as $category => $items)
                    <div class="mb-5">
                        <h2 class="h4 mb-3">{{ $category }}</h2>
                        <div class="accordion" id="faq-{{ Str::slug($category) }}">
                            @foreach($items as $faq)
                                <div class="accordion-item">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#faq-{{ $faq->id }}"
                                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                            {{ $faq->question }}
                                        </button>
                                    </h3>
                                    <div id="faq-{{ $faq->id }}"
                                         class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                         data-bs-parent="#faq-{{ Str::slug($category) }}">
                                        <div class="accordion-body">
                                            {!! $faq->answer !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif

            @if($uncategorized->count() > 0)
                <div class="mb-5">
                    <div class="accordion" id="faq-general">
                        @foreach($uncategorized as $faq)
                            <div class="accordion-item">
                                <h3 class="accordion-header">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#faq-{{ $faq->id }}"
                                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                        {{ $faq->question }}
                                    </button>
                                </h3>
                                <div id="faq-{{ $faq->id }}"
                                     class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                     data-bs-parent="#faq-general">
                                    <div class="accordion-body">
                                        {!! $faq->answer !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <p class="text-muted">No FAQs available at this time.</p>
            </div>
        @endif
    </div>
</section>
@endsection
