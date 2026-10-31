@extends('layouts.app')

@section('title', 'Home')

@section('meta')
    <link rel="canonical" href="{{ route('home', [], false) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e(config('app.name')) }}">
    <meta property="og:url" content="{{ route('home', [], false) }}">
@endsection

@php
    try {
        $settingsService = app(\App\Services\SettingsService::class);
        $tagline = $settingsService->get('general.tagline', 'Texas Made. Seriously Good Jerky.');
    } catch (\Throwable $e) {
        $tagline = 'Texas Made. Seriously Good Jerky.';
    }

    $branding = \App\Models\SiteBranding::getOrCreateSafely();
    $heroLogo = $branding && $branding->hasLogo()
        ? $branding->getLogoUrl()
        : asset('brand/sograte-logo.png');
@endphp

@section('content')
<section class="home-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <p class="home-hero__script">BBQ &amp; Beef Jerky</p>
                <h1 class="home-hero__title">{{ $tagline }}</h1>
                <p class="home-hero__text">
                    Small-batch beef jerky with bold BBQ flavor, made in Texas and built for sharing.
                </p>
                <div class="home-hero__actions">
                    <a href="{{ route('shop.index') }}" class="btn btn-brand btn-lg">Shop Jerky</a>
                    <a href="{{ route('contact.show') }}" class="btn btn-brand-outline btn-lg">Contact Us</a>
                </div>
            </div>
            <div class="col-lg-5 text-center text-lg-end">
                <img src="{{ $heroLogo }}" alt="SOGRATE BBQ and Beef Jerky" class="home-hero__logo mx-lg-auto" loading="eager" decoding="async">
            </div>
        </div>
    </div>
</section>

<section class="home-proof" aria-label="SOGRATE highlights">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="home-proof__item">
                    <span class="home-proof__icon" aria-hidden="true">&#10003;</span>
                    <span>Texas made, seriously good jerky</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="home-proof__item">
                    <span class="home-proof__icon" aria-hidden="true">&#10003;</span>
                    <span>Small-batch craft and bold flavor</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="home-proof__item">
                    <span class="home-proof__icon" aria-hidden="true">&#10003;</span>
                    <span>Built for cookouts, road trips, and sharing</span>
                </div>
            </div>
        </div>
    </div>
</section>

@if($featuredProducts->count() > 0)
<section class="home-featured" id="featured-jerky">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Featured Jerky</span>
            <h2 class="section-heading__title">Seriously good jerky, ready to share.</h2>
            <p class="section-heading__subtitle">Small-batch SOGRATE favorites, made in Texas.</p>
        </div>

        <div class="row g-4">
            @foreach($featuredProducts as $product)
                <div class="col-sm-6 col-lg-4">
                    @include('shop._product-card', ['product' => $product])
                </div>
            @endforeach
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('shop.index') }}" class="btn btn-brand-outline">Shop All Jerky</a>
        </div>
    </div>
</section>
@endif

<section class="home-quick-order">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="home-quick-order__tag">Quick Order</span>
                <h2>Stock up without the wait.</h2>
                <p class="home-quick-order__text">
                    Quick Order is coming in a future release. For now, reach out to the SOGRATE team directly.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('contact.show') }}" class="btn btn-light btn-lg">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<section class="home-story" id="our-story">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <div class="section-heading mb-4">
                    <span class="eyebrow">Our Story</span>
                    <h2 class="section-heading__title">From backyard BBQ to seriously good jerky.</h2>
                </div>
                <div class="home-story__text">
                    <p>
                        SOGRATE began with backyard BBQ and a simple phrase: &ldquo;Man, this is so great.&rdquo;
                        That phrase became our name and carried us into our first BBQ competition.
                    </p>
                    <p>
                        We later expanded into vending and catering. Beef jerky started as a way to promote the
                        brand and share our flavor, eventually becoming part of a retail operation that also sold
                        BBQ and homebrew supplies.
                    </p>
                    <p class="mb-0">
                        That retail chapter has closed. Today our focus is beef jerky: Texas made, small-batch,
                        and seriously good.
                    </p>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="home-story__quote">
                    <p class="home-story__quote-text">&ldquo;Man, this is so great.&rdquo;</p>
                    <p class="home-story__quote-caption">The phrase that started SOGRATE</p>
                </div>
            </div>
        </div>
    </div>
</section>

@if($testimonials->count() > 0)
<section class="section">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">Testimonials</span>
            <h2 class="section-heading__title">What Our Customers Say</h2>
        </div>

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

@if($recentPosts->count() > 0)
<section class="home-blog">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">From the Blog</span>
            <h2 class="section-heading__title">Latest from SOGRATE</h2>
            <p class="section-heading__subtitle">Recipes, brand updates, and behind-the-scenes notes from the pit.</p>
        </div>

        <div class="row g-4">
            @foreach($recentPosts as $post)
                <div class="col-md-6 col-lg-4">
                    @include('blog._post-card', ['post' => $post])
                </div>
            @endforeach
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('blog.index') }}" class="btn btn-brand-outline">View All Articles</a>
        </div>
    </div>
</section>
@endif

@if($faqs->count() > 0)
<section class="home-faq">
    <div class="container">
        <div class="section-heading">
            <span class="eyebrow">FAQs</span>
            <h2 class="section-heading__title">Frequently Asked Questions</h2>
            <p class="section-heading__subtitle">Find answers to common questions.</p>
        </div>

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
                    <a href="{{ route('faq.index') }}" class="btn btn-brand-outline">View All FAQs</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<x-cta-section
    heading="Ready for Seriously Good Jerky?"
    primary-label="Contact Us"
    primary-url="{{ route('contact.show') }}"
/>
@endsection

@section('structured_data')
<x-structured-data :data="app(\App\Services\StructuredDataService::class)->all()" />
@endsection
