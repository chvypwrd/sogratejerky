@extends('layouts.app')

@section('title', 'Our Instructors')

@section('content')
<section class="py-5">
    <div class="container">
        <header class="mb-5">
            <h1>Our Instructors</h1>
            <p class="lead text-muted">Meet the experienced team guiding your kickboxing journey.</p>
        </header>

        @if($instructors->count() > 0)
            <div class="row g-4">
                @foreach($instructors as $instructor)
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('instructors.show', $instructor->slug) }}" class="text-decoration-none">
                            <div class="card h-100">
                                @if($instructor->hasMedia('portrait'))
                                    <img src="{{ $instructor->getFirstMediaUrl('portrait', 'thumb') }}"
                                         alt="{{ e($instructor->getFirstMedia('portrait')?->getCustomProperty('alt_text', $instructor->name)) }}"
                                         class="card-img-top" style="height: 250px; object-fit: cover;">
                                @endif
                                <div class="card-body">
                                    <h5 class="card-title text-dark">{{ $instructor->name }}</h5>
                                    @if($instructor->title)
                                        <p class="text-muted mb-2">{{ $instructor->title }}</p>
                                    @endif
                                    @if($instructor->short_bio)
                                        <p class="card-text text-muted">{{ Str::limit($instructor->short_bio, 120) }}</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <p class="text-muted">No instructors available at this time.</p>
            </div>
        @endif
    </div>
</section>
@endsection
