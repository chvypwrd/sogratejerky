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
                    <a href="{{ route('classes.index') }}" class="btn btn-coral btn-lg">Explore Classes</a>
                    @if(route('contact.show', [], false) !== url('/contact'))
                        <a href="{{ route('contact.show') }}" class="btn btn-coral-outline btn-lg">Contact Us</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Beginner reassurance --}}
<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2>Never Tried Kickboxing? Perfect.</h2>
                <p class="lead text-muted mt-3">
                    Our classes are designed for all experience levels. Whether you've never thrown a punch or you're a seasoned pro,
                    you'll find a supportive environment where you can learn at your own pace, build real skills, and feel amazing.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- Benefits: Confidence, Strength, Community --}}
<section class="py-5">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3>Confidence</h3>
                <p class="text-muted">
                    Build real confidence through skill mastery and personal achievement in a supportive environment.
                </p>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <h3>Strength</h3>
                <p class="text-muted">
                    Develop physical and mental strength with expert-guided kickboxing training designed for every fitness level.
                </p>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                </div>
                <h3>Community</h3>
                <p class="text-muted">
                    Join a supportive community of women who lift each other up and train together in a safe, welcoming space.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- Featured Classes --}}
@if($featuredClasses->count() > 0)
<section class="py-5 bg-light">
    <div class="container">
        <header class="text-center mb-5">
            <h2>Our Classes</h2>
            <p class="lead text-muted">Find the right program for your goals and experience level.</p>
        </header>

        <div class="row g-4">
            @foreach($featuredClasses as $class)
                <div class="col-md-6 col-lg-4">
                    <x-class-card :class="$class" />
                </div>
            @endforeach
        </div>

        <div class="text-center mt-5">
            <a href="{{ route('classes.index') }}" class="btn btn-brand">View All Classes</a>
        </div>
    </div>
</section>
@endif

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

{{-- Instructor Spotlight --}}
@if($instructors->count() > 0)
<section class="py-5 bg-light">
    <div class="container">
        <header class="text-center mb-5">
            <h2>Meet Your Instructors</h2>
            <p class="lead text-muted">Experienced, passionate, and dedicated to your success.</p>
        </header>

        <div class="row g-4 justify-content-center">
            @foreach($instructors as $instructor)
                <div class="col-md-6 col-lg-4">
                    <x-instructor-card :instructor="$instructor" />
                </div>
            @endforeach
        </div>

        <div class="text-center mt-5">
            <a href="{{ route('instructors.index') }}" class="btn btn-brand">Meet the Full Team</a>
        </div>
    </div>
</section>
@endif

{{-- What to Expect --}}
<section class="py-5">
    <div class="container">
        <header class="text-center mb-5">
            <h2>What to Expect</h2>
            <p class="lead text-muted">Getting started is simple.</p>
        </header>

        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="display-6 fw-bold text-primary mb-3">1</div>
                <h3>Choose Your Class</h3>
                <p class="text-muted">Browse our programs and find the class that fits your goals and schedule.</p>
            </div>
            <div class="col-md-4">
                <div class="display-6 fw-bold text-primary mb-3">2</div>
                <h3>Show Up &amp; Train</h3>
                <p class="text-muted">Arrive, meet your instructor, and start learning real kickboxing skills in a supportive environment.</p>
            </div>
            <div class="col-md-4">
                <div class="display-6 fw-bold text-primary mb-3">3</div>
                <h3>Feel the Difference</h3>
                <p class="text-muted">Walk away stronger, more confident, and excited to come back for more.</p>
            </div>
        </div>
    </div>
</section>

{{-- Testimonials --}}
@if($testimonials->count() > 0)
<section class="py-5 bg-light">
    <div class="container">
        <header class="text-center mb-5">
            <h2>What Our Members Say</h2>
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
    primary-label="Explore Classes"
    primary-url="{{ route('classes.index') }}"
    secondary-label="Contact Us"
    secondary-url="{{ route('contact.show', [], false) !== url('/contact') ? route('contact.show') : null }}"
/>
@endsection

@section('structured_data')
<x-structured-data :data="app(\App\Services\StructuredDataService::class)->all()" />
@endsection
