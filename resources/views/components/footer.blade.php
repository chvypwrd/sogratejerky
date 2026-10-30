@php
    try {
        $settingsService = app(\App\Services\SettingsService::class);
        $siteName = $settingsService->get('general.site_name', config('app.name', 'SOGRATE'));
        $contactEmail = $settingsService->get('contact.email', '');
        $contactPhone = $settingsService->get('contact.phone', '');
        $contactAddress = $settingsService->get('contact.address', '');
        $facebook = $settingsService->get('social.facebook', '');
        $instagram = $settingsService->get('social.instagram', '');
        $youtube = $settingsService->get('social.youtube', '');
        $footerText = $settingsService->get('appearance.footer_text', '');
    } catch (\Throwable $e) {
        $siteName = config('app.name', 'SOGRATE');
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

    $hasContact = $contactEmail || $contactPhone || $contactAddress;
    $hasSocial = $facebook || $instagram || $youtube;
@endphp

<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="footer-brand">
                    {{ $siteName }}
                </div>
                @if($footerText)
                    <p class="footer-text">{!! nl2br(e($footerText)) !!}</p>
                @endif
            </div>

            <div class="col-lg-3 col-md-6">
                @if($footerMenu)
                    <div class="footer-nav">
                        <h6 class="footer-heading">Navigation</h6>
                        {!! $footerMenu !!}
                    </div>
                @endif
            </div>

            <div class="col-lg-3 col-md-6">
                @if($hasContact)
                    <div class="footer-contact">
                        <h6 class="footer-heading">Contact</h6>
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
                @endif
            </div>

            <div class="col-lg-3 col-md-6">
                @if($hasSocial)
                    <div class="footer-social">
                        <h6 class="footer-heading">Follow Us</h6>
                        <ul class="list-unstyled">
                            @if($facebook)
                                <li><a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook">Facebook</a></li>
                            @endif
                            @if($instagram)
                                <li><a href="{{ $instagram }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram">Instagram</a></li>
                            @endif
                            @if($youtube)
                                <li><a href="{{ $youtube }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube">YouTube</a></li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <div class="footer-copyright mt-4 pt-3 border-top">
            &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
        </div>
    </div>
</footer>
