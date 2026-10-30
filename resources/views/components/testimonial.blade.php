@props([
    'testimonial',
])

<blockquote class="testimonial-card">
    <p class="testimonial-card__quote">{{ $testimonial->quote }}</p>
    <footer class="testimonial-card__author">
        <p class="testimonial-card__name">{{ $testimonial->name }}</p>
        @if($testimonial->attribution)
            <span class="testimonial-card__role">{{ $testimonial->attribution }}</span>
        @endif
    </footer>
</blockquote>
