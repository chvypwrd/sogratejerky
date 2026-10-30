@php
    try {
        $settingsService = app(\App\Services\SettingsService::class);
        $siteName = $settingsService->get('general.site_name', config('app.name', 'SOGRATE'));
    } catch (\Throwable $e) {
        $siteName = config('app.name', 'SOGRATE');
    }

    try {
        $menuService = app(\App\Services\MenuService::class);
        $primaryMenu = $menuService->getRenderedMenu(\App\Enums\MenuLocation::Primary);
    } catch (\Throwable $e) {
        $primaryMenu = '';
    }

    $branding = \App\Models\SiteBranding::getOrCreateSafely();
    $hasLogo = $branding?->hasLogo() ?? false;
    $logoUrl = $hasLogo ? $branding->getLogoUrl() : '';

    $contactRouteExists = route('contact.show', [], false) !== url('/contact');
    $menuHasContact = str_contains($primaryMenu, 'contact');
    $showContactCta = $contactRouteExists && !$menuHasContact;
@endphp

<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                @if($hasLogo && $logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="navbar-brand-img">
                @else
                    {{ $siteName }}
                @endif
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNav"
                aria-controls="mainNav"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <div class="d-flex flex-grow-1 justify-content-center">
                    <ul class="navbar-nav gap-1 gap-lg-2 align-items-center">
                        {!! $primaryMenu !!}
                    </ul>
                </div>

                @if($showContactCta)
                    <a href="{{ route('contact.show') }}" class="btn-cta d-none d-xl-inline-flex">Contact Us</a>
                @endif
            </div>
        </div>
    </nav>
</header>
