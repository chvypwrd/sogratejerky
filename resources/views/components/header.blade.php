@php
    try {
        $settingsService = app(\App\Services\SettingsService::class);
        $siteName = $settingsService->get('general.site_name', config('app.name', 'Sograte'));
    } catch (\Throwable $e) {
        $siteName = config('app.name', 'Sograte');
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

    $cartCount = 0;
    try {
        $cartCount = app(\App\Services\CartService::class)->existingItemCount();
    } catch (\Throwable $e) {
        $cartCount = 0;
    }

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

                <div class="header-actions">
                    @auth
                        <div class="dropdown">
                            <button
                                class="header-account dropdown-toggle"
                                type="button"
                                id="customer-account-menu"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-haspopup="true"
                            >
                                My Account
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="customer-account-menu">
                                <li><a class="dropdown-item" href="{{ route('account.dashboard') }}">Dashboard</a></li>
                                <li><a class="dropdown-item" href="{{ route('account.orders.index') }}">Orders</a></li>
                                <li><a class="dropdown-item" href="{{ route('account.profile.edit') }}">Profile</a></li>
                                <li><a class="dropdown-item" href="{{ route('account.addresses.index') }}">Addresses</a></li>
                                <li><a class="dropdown-item" href="{{ route('account.payment-methods.index') }}">Payment Methods</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('customer.logout') }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Sign Out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('customer.login') }}" class="header-account">Sign In</a>
                        <a href="{{ route('customer.register') }}" class="header-account header-account--strong">Create Account</a>
                    @endauth
                    <a href="{{ route('cart.index') }}" class="header-cart" aria-label="Cart, {{ $cartCount }} {{ $cartCount === 1 ? 'item' : 'items' }}">
                        Cart <span class="header-cart__count">{{ $cartCount }}</span>
                    </a>
                    @if($showContactCta)
                        <a href="{{ route('contact.show') }}" class="btn-cta">Contact Us</a>
                    @endif
                </div>
            </div>
        </div>
    </nav>
</header>
