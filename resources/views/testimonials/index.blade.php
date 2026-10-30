@extends('layouts.app')

@section('title', 'Testimonials')

@section('content')
<section class="section">
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h1>What Our Students Say</h1>
            <p>Real experiences from our customers</p>
        </div>

        @if($testimonials->isEmpty())
            <div class="text-center py-5">
                <p class="text-muted">No testimonials yet. Check back soon!</p>
            </div>
        @else
            <div class="row g-4">
                @foreach($testimonials as $testimonial)
                    <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3) * 100 }}">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <i class="fas fa-quote-left text-primary opacity-25" style="font-size: 2rem;"></i>
                                </div>
                                <p class="card-text mb-4">{{ $testimonial->quote }}</p>
                                <hr>
                                <div class="d-flex align-items-center">
                                    <div class="ms-3">
                                        <h6 class="mb-0 fw-bold">{{ $testimonial->name }}</h6>
                                        @if($testimonial->attribution)
                                            <small class="text-muted">{{ $testimonial->attribution }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
