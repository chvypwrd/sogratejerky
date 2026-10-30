@extends('layouts.app')

@section('title', 'Our Classes')

@section('content')
<section class="py-5">
    <div class="container">
        <header class="mb-5">
            <h1>Our Classes</h1>
            <p class="lead text-muted">Discover the right kickboxing program for your goals and experience level.</p>
        </header>

        @if($classes->count() > 0)
            <div class="row g-4">
                @foreach($classes as $class)
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('classes.show', $class->slug) }}" class="text-decoration-none">
                            <div class="card h-100">
                                @if($class->hasMedia('featured'))
                                    <img src="{{ $class->getFirstMediaUrl('featured', 'thumb') }}"
                                         alt="{{ e($class->getFirstMedia('featured')?->getCustomProperty('alt_text', $class->title)) }}"
                                         class="card-img-top" style="height: 200px; object-fit: cover;">
                                @endif
                                <div class="card-body">
                                    <h5 class="card-title text-dark">{{ $class->title }}</h5>
                                    @if($class->short_description)
                                        <p class="card-text text-muted">{{ Str::limit($class->short_description, 120) }}</p>
                                    @endif
                                    <div class="d-flex gap-2">
                                        @if($class->level)
                                            <span class="badge bg-secondary">{{ $class->level }}</span>
                                        @endif
                                        @if($class->duration)
                                            <span class="badge bg-light text-dark">{{ $class->duration }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <p class="text-muted">No classes available at this time. Please check back soon!</p>
            </div>
        @endif
    </div>
</section>
@endsection
