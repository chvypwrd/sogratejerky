@php
    try {
        $settingsService = app(\App\Services\SettingsService::class);
        $siteName = $settingsService->get('general.site_name', config('app.name', 'SOGRATE'));
        $tagline = $settingsService->get('general.tagline', 'Texas Made. Seriously Good Jerky.');
        $contactEmail = $settingsService->get('contact.email', '');
        $contactPhone = $settingsService->get('contact.phone', '');
        $contactAddress = $settingsService->get('contact.address', '');
        $facebook = $settingsService->get('social.facebook', '');
        $instagram = $settingsService->get('social.instagram', '');
        $youtube = $settingsService->get('social.youtube', '');
        $footerText = $settingsService->get('appearance.footer_text', '');
    } catch (\Throwable $e) {
        $siteName = config('app.name', 'SOGRATE');
        $tagline = 'Texas Made. Seriously Good Jerky.';
        $contactEmail = '';
        $contactPhone = '';
        $contactAddress = '';
        $facebook = '';
        $instagram = '';
        $youtube = '';
        $footerText = '';
    }

    try {
        $footerMenu = app(\App\Services\MenuService::class)->getRenderedMenu(\App\Enums\MenuLocation::Footer);
    } catch (\Throwable $e) {
        $footerMenu = '';
    }

    $branding = \App\Models\SiteBranding::getOrCreateSafely();
    $hasLogo = $branding ? $branding->hasLogo() : false;
    $footerLogo = $hasLogo
        ? $branding->getLogoUrl()
        : asset('brand/sograte-logo-reversed.png');

    $hasContact = $contactEmail || $contactPhone || $contactAddress;
    $hasSocial = $facebook || $instagram || $youtube;
@endphp

<footer class="site-footer">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4">
                <a href="{{ route('home') }}" class="footer-logo-link" aria-label="{{ $siteName }} home">
                    <img src="{{ $footerLogo }}" alt="{{ $siteName }}" class="footer-logo {{ $hasLogo ? 'footer-logo--uploaded' : '' }}" loading="lazy" decoding="async">
                </a>
                <p class="footer-tagline">{{ $tagline }}</p>
                @if($footerText)
                    <p class="footer-text">{!! nl2br(e($footerText)) !!}</p>
                @endif
            </div>

            @if($footerMenu)
                <div class="col-6 col-lg-2">
                    <div class="footer-nav">
                        <h2 class="footer-heading">Explore</h2>
                        {!! $footerMenu !!}
                    </div>
                </div>
            @endif

            @if($hasContact)
                <div class="col-6 col-lg-3">
                    <div class="footer-contact">
                        <h2 class="footer-heading">Contact</h2>
                        @if($contactPhone)
                            <p><a href="tel:{{ $contactPhone }}">{{ $contactPhone }}</a></p>
                        @endif
                        @if($contactEmail)
                            <p><a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></p>
                        @endif
                        @if($contactAddress)
                            <p>{!! nl2br(e($contactAddress)) !!}</p>
                        @endif
                    </div>
                </div>
            @endif

            @if($hasSocial)
                <div class="col-lg-3">
                    <div class="footer-social">
                        <h2 class="footer-heading">Follow Us</h2>
                        <ul>
                            @if($facebook)
                                <li><a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer">Facebook</a></li>
                            @endif
                            @if($instagram)
                                <li><a href="{{ $instagram }}" target="_blank" rel="noopener noreferrer">Instagram</a></li>
                            @endif
                            @if($youtube)
                                <li><a href="{{ $youtube }}" target="_blank" rel="noopener noreferrer">YouTube</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="footer-bottom">
            <p class="footer-copyright">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
            <p class="footer-made">Texas Made. Seriously Good Jerky.</p>
        </div>
    </div>
</footer>
