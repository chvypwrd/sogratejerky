@props([
    'heading' => 'Ready to get started?',
    'primaryLabel' => 'Get Started',
    'primaryUrl' => '#',
    'secondaryLabel' => null,
    'secondaryUrl' => null,
])

<section class="cta-section py-5 bg-primary text-white text-center">
    <div class="container">
        <h2 class="mb-4 text-white">{{ $heading }}</h2>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ $primaryUrl }}" class="btn btn-light btn-lg">{{ $primaryLabel }}</a>
            @if($secondaryLabel && $secondaryUrl)
                <a href="{{ $secondaryUrl }}" class="btn btn-outline-light btn-lg">{{ $secondaryLabel }}</a>
            @endif
        </div>
    </div>
</section>
