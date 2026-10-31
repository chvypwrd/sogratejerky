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
    @php
        $pageMetaDescription = trim((string) $__env->yieldContent('meta_description'));
    @endphp
    @if($pageMetaDescription !== '')
        <meta name="description" content="{{ e($pageMetaDescription) }}">
    @elseif($metaDescription ?? $defaultDescription)
        <meta name="description" content="{{ e($metaDescription ?? $defaultDescription) }}">
    @endif
    <meta name="theme-color" content="#9B300F">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @if($branding && $branding->hasMedia('favicon'))
        <link rel="icon" href="{{ $branding->getFirstMediaUrl('favicon') }}">
        <link rel="apple-touch-icon" href="{{ $branding->getFirstMediaUrl('favicon') }}">
    @else
        <link rel="icon" href="{{ asset('favicon/favicon-32.png') }}" sizes="32x32" type="image/png">
        <link rel="icon" href="{{ asset('favicon/favicon-192.png') }}" sizes="192x192" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('favicon/apple-touch-icon.png') }}">
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
