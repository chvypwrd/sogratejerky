@props([
    'title' => null,
    'description' => null,
    'robots' => null,
    'canonicalUrl' => null,
    'ogImage' => null,
])

@if($description)
    <meta name="description" content="{{ e($description) }}">
@endif

@if($robots)
    <meta name="robots" content="{{ e($robots) }}">
@endif

@if($canonicalUrl)
    <link rel="canonical" href="{{ e($canonicalUrl) }}">
@endif

<meta property="og:type" content="website">
<meta property="og:title" content="{{ e($title ?? config('app.name')) }}">
@if($description)
    <meta property="og:description" content="{{ e($description) }}">
@endif
@if($canonicalUrl)
    <meta property="og:url" content="{{ e($canonicalUrl) }}">
@endif
@if($ogImage)
    <meta property="og:image" content="{{ e($ogImage) }}">
@endif

<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ e($title ?? config('app.name')) }}">
@if($description)
    <meta name="twitter:description" content="{{ e($description) }}">
@endif
@if($ogImage)
    <meta name="twitter:image" content="{{ e($ogImage) }}">
@endif
