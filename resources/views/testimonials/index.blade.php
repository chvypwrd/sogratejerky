@extends('layouts.app')

@section('title', 'Testimonials')

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Testimonials</span>
            <h1>What Our Customers Say</h1>
            <p class="lead text-muted">Real experiences from our customers.</p>
        </header>

        @if($testimonials->isEmpty())
            <div class="empty-state">
                <p class="mb-0">No testimonials yet. Check back soon.</p>
            </div>
        @else
            <div class="row g-4">
                @foreach($testimonials as $testimonial)
                    <div class="col-md-6 col-lg-4">
                        <x-testimonial :testimonial="$testimonial" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
