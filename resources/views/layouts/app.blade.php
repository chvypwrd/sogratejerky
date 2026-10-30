@php
    try {
        $settingsService = app(\App\Services\SettingsService::class);
        $siteName = $settingsService->get('general.site_name', config('app.name', 'SOGRATE'));
        $defaultDescription = $settingsService->get('seo.default_description', '');
    } catch (\Throwable $e) {
        $siteName = config('app.name', 'SOGRATE');
        $defaultDescription = '';
    }
    $branding = \App\Models\SiteBranding::getOrCreateSafely();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $metaDescription ?? $defaultDescription }}">

    @if($branding && $branding->hasMedia('favicon'))
        <link rel="icon" href="{{ $branding->getFirstMediaUrl('favicon') }}">
    @else
        <link rel="icon" href="{{ asset('favicon/icon.svg') }}" type="image/svg+xml">
    @endif

    @php
        try {
            $gscKey = app(\App\Services\SettingsService::class)->get('seo.google_site_verification', '');
        } catch (\Throwable $e) {
            $gscKey = '';
        }
    @endphp
    @if($gscKey)
        <meta name="google-site-verification" content="{{ e($gscKey) }}">
    @endif

    @yield('meta')

    <title>@hasSection('title')@yield('title') | {{ $siteName }}@else{{ $siteName }}@endif</title>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>

    @include('components.header')

    <main id="main-content">
        @yield('content')
    </main>

    @include('components.footer')

    @yield('structured_data')

    @yield('scripts')
</body>
</html>
