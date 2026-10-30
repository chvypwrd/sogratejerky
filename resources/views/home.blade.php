@extends('layouts.app')

@section('title', 'Home')

@section('meta')
    <link rel="canonical" href="{{ route('home', [], false) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e(config('app.name')) }}">
    <meta property="og:url" content="{{ route('home', [], false) }}">
@endsection

@section('content')
{{-- Hero --}}
<section class="hero-section py-5">
    <div class="container">
        <div class="row align-items-center py-5">
            <div class="col-lg-8 mx-auto text-center">
                <h1>Texas Made. Seriously Good Jerky.</h1>
                <p class="lead mt-3 mb-4">
                    SOGRATE is a Texas-made BBQ and beef jerky brand built on serious flavor and honest craft.
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="{{ route('contact.show') }}" class="btn btn-coral btn-lg">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Why SOGRATE --}}
<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h2>Why SOGRATE?</h2>
                <p class="lead text-muted">
                    We're not just a jerky brand. We're a Texas-made BBQ and beef jerky company
                    built around serious flavor, quality ingredients, and a craft worth sharing.
                </p>
                <ul class="list-unstyled mt-4">
                    <li class="mb-3">
                        <strong>Texas made</strong>
                        <p class="text-muted mb-0">Every batch is made in Texas with a focus on real BBQ flavor and quality.</p>
                    </li>
                    <li class="mb-3">
                        <strong>Small-batch craft</strong>
                        <p class="text-muted mb-0">We take the time to get the seasoning, smoke, and texture right in every batch.</p>
                    </li>
                    <li class="mb-3">
                        <strong>Seriously good flavor</strong>
                        <p class="text-muted mb-0">Bold, honest jerky made to be shared and remembered.</p>
                    </li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="bg-light rounded-4 p-5 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" class="text-primary" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <h3 class="mt-4">Made in Texas</h3>
                    <p class="text-muted">
                        From the first batch to the last, SOGRATE is built around serious flavor and
                        a straightforward commitment to doing jerky right.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Testimonials --}}
@if($testimonials->count() > 0)
<section class="py-5 bg-light">
    <div class="container">
        <header class="text-center mb-5">
            <h2>What Our Customers Say</h2>
        </header>

        <div class="row g-4 justify-content-center">
            @foreach($testimonials as $testimonial)
                <div class="col-md-6 col-lg-4">
                    <x-testimonial :testimonial="$testimonial" />
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- FAQ Preview --}}
@if($faqs->count() > 0)
<section class="py-5">
    <div class="container">
        <header class="text-center mb-5">
            <h2>Frequently Asked Questions</h2>
            <p class="lead text-muted">Find answers to common questions.</p>
        </header>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="home-faq">
                    @foreach($faqs as $faq)
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#home-faq-{{ $faq->id }}"
                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ $faq->question }}
                                </button>
                            </h3>
                            <div id="home-faq-{{ $faq->id }}"
                                 class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                 data-bs-parent="#home-faq">
                                <div class="accordion-body">
                                    {!! $faq->answer !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('faq.index') }}" class="btn btn-brand">View All FAQs</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Final CTA --}}
<x-cta-section
    heading="Ready for Seriously Good Jerky?"
    primary-label="Contact Us"
    primary-url="{{ route('contact.show') }}"
/>
@endsection

@section('structured_data')
<x-structured-data :data="app(\App\Services\StructuredDataService::class)->all()" />
@endsection
