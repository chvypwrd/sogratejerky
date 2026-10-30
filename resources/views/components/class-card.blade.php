@props([
    'class',
])

<a href="{{ route('classes.show', $class->slug) }}" class="text-decoration-none">
    <div class="card h-100">
        @if($class->hasMedia('featured'))
            @php
                $media = $class->getFirstMedia('featured');
                $altText = $media->getCustomProperty('alt_text', $class->title);
            @endphp
            <img src="{{ $media->getFullUrl() }}"
                 alt="{{ e($altText) }}"
                 class="card-img-top"
                 loading="lazy"
                 style="height: 200px; object-fit: cover;">
        @else
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                <span class="text-muted">No image</span>
            </div>
        @endif

        <div class="card-body">
            <h5 class="card-title text-dark">{{ $class->title }}</h5>

            @if($class->short_description)
                <p class="card-text text-muted">{{ Str::limit($class->short_description, 120) }}</p>
            @endif

            <div class="d-flex gap-2 flex-wrap">
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
