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
    $logoUrl = $branding && $branding->hasLogo()
        ? $branding->getLogoUrl()
        : asset('brand/sograte-wordmark.png');

    $contactRouteExists = route('contact.show', [], false) !== url('/contact');
    $menuHasContact = str_contains($primaryMenu, 'contact');
    $showContactCta = $contactRouteExists && ! $menuHasContact;
@endphp

<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}" aria-label="{{ $siteName }} home">
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" loading="eager" decoding="async">
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
                <ul class="navbar-nav mx-lg-auto align-items-lg-center">
                    {!! $primaryMenu !!}
                </ul>

                @if($showContactCta)
                    <div class="header-actions">
                        <a href="{{ route('contact.show') }}" class="btn-cta">Contact Us</a>
                    </div>
                @endif
            </div>
        </div>
    </nav>
</header>
