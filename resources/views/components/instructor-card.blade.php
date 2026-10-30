@props([
    'instructor',
])

<a href="{{ route('instructors.show', $instructor->slug) }}" class="text-decoration-none">
    <div class="card h-100">
        @if($instructor->hasMedia('portrait'))
            @php
                $media = $instructor->getFirstMedia('portrait');
                $altText = $media->getCustomProperty('alt_text', $instructor->name);
            @endphp
            <img src="{{ $media->getFullUrl() }}"
                 alt="{{ e($altText) }}"
                 class="card-img-top"
                 loading="lazy"
                 style="height: 250px; object-fit: cover;">
        @else
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 250px;">
                <span class="text-muted">No photo</span>
            </div>
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
