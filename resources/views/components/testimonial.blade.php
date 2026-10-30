@props([
    'testimonial',
])

<blockquote class="testimonial blockquote">
    <p class="mb-3">{{ $testimonial->quote }}</p>
    <footer class="blockquote-footer">
        {{ $testimonial->name }}
        @if($testimonial->attribution)
            <cite class="d-block small text-muted">{{ $testimonial->attribution }}</cite>
        @endif
    </footer>
</blockquote>
