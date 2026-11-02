@extends('layouts.app')

@section('title', 'Blog')

@section('meta')
    @php
        $canonicalUrl = $posts->currentPage() > 1 ? $posts->url($posts->currentPage()) : route('blog.index');
    @endphp
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ e(config('app.name')) }} - Blog">
    <meta property="og:url" content="{{ $canonicalUrl }}">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">From the Pit</span>
            <div class="row align-items-end g-3">
                <div class="col-lg-7">
                    <h1>Sograte Blog</h1>
                    <p class="lead text-muted">Stories, recipes, and updates from the Sograte kitchen.</p>
                </div>
                <div class="col-lg-5">
                    <form action="{{ route('blog.search') }}" method="GET" class="blog-search" role="search">
                        <label for="blog-search" class="visually-hidden">Search articles</label>
                        <div class="input-group">
                            <input type="search" class="form-control" id="blog-search" name="q" placeholder="Search articles" maxlength="100">
                            <button type="submit" class="btn btn-brand">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </header>

        @include('blog._posts', ['posts' => $posts])
    </div>
</section>
@endsection
