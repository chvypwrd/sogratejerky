@extends('layouts.app')

@section('title', $archiveType . ': ' . $heading)

@section('meta')
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e(config('app.name')) }} - {{ $heading }}">
    <meta property="og:url" content="{{ url()->current() }}">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Blog {{ $archiveType }}</span>
            <h1>{{ $heading }}</h1>
            @if($description)
                <p class="lead text-muted">{{ $description }}</p>
            @endif
            <a href="{{ route('blog.index') }}" class="small text-muted">&larr; All articles</a>
        </header>

        @include('blog._posts', ['posts' => $posts])
    </div>
</section>
@endsection
