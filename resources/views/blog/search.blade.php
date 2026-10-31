@extends('layouts.app')

@section('title', 'Search Blog')

@section('meta')
    <meta name="robots" content="noindex,follow">
    <link rel="canonical" href="{{ route('blog.search') }}">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Search</span>
            <h1>Search the Blog</h1>
            <p class="lead text-muted">
                @if($term !== '')
                    Results for &ldquo;{{ $term }}&rdquo;
                @else
                    Enter a term to search published articles.
                @endif
            </p>

            <form action="{{ route('blog.search') }}" method="GET" class="blog-search" role="search">
                <label for="blog-search" class="visually-hidden">Search articles</label>
                <div class="input-group">
                    <input type="search" class="form-control" id="blog-search" name="q" value="{{ $term }}" placeholder="Search articles" maxlength="100">
                    <button type="submit" class="btn btn-brand">Search</button>
                </div>
            </form>
        </header>

        @include('blog._posts', ['posts' => $posts])
    </div>
</section>
@endsection
