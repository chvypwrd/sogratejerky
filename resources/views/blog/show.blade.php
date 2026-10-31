@extends('layouts.app')

@section('title', $post->seo_title ?: $post->title)

@section('meta')
    @php
        $seoDescription = $post->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) ($post->excerpt ?: $post->content)), 160);
        $canonicalUrl = route('blog.show', $post->slug);
        $featuredMedia = $post->getFirstMedia('featured');
    @endphp

    @if($seoDescription)
        <meta name="description" content="{{ e($seoDescription) }}">
    @endif
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ e($post->seo_title ?: $post->title) }}">
    @if($seoDescription)
        <meta property="og:description" content="{{ e($seoDescription) }}">
    @endif
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if($featuredMedia)
        <meta property="og:image" content="{{ $featuredMedia->getUrl('card') }}">
    @endif
    <meta property="article:published_time" content="{{ $post->published_at?->toIso8601String() }}">
@endsection

@section('content')
<article class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <header class="page-header blog-article-header">
                    <span class="eyebrow">From the Pit</span>
                    <h1>{{ $post->title }}</h1>

                    <div class="blog-meta">
                        <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('F j, Y') }}</time>
                        <span aria-hidden="true">&middot;</span>
                        <span>By
                            @if($post->author_id)
                                <a href="{{ route('blog.author', $post->author_id) }}">{{ $post->author_display_name }}</a>
                            @else
                                {{ $post->author_display_name }}
                            @endif
                        </span>
                    </div>

                    @if($post->categories->isNotEmpty())
                        <div class="blog-categories">
                            @foreach($post->categories as $category)
                                <a href="{{ route('blog.category', $category->slug) }}">{{ $category->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </header>

                @if($post->hasMedia('featured'))
                    @php
                        $media = $post->getFirstMedia('featured');
                        $altText = $media->getCustomProperty('alt_text', $post->title);
                    @endphp
                    <div class="blog-featured-image mb-4">
                        <img src="{{ $media->getUrl('card') }}" alt="{{ e($altText) }}" class="img-fluid">
                    </div>
                @endif

                <div class="rich-content blog-content">
                    {!! $post->content !!}
                </div>

                @if($post->tags->isNotEmpty())
                    <div class="blog-tags mt-4">
                        <span class="blog-tags__label">Tags:</span>
                        @foreach($post->tags as $tag)
                            <a href="{{ route('blog.tag', $tag->slug) }}">{{ $tag->name }}</a>
                        @endforeach
                    </div>
                @endif

                <section class="blog-comments mt-5" id="comments">
                    <h2 class="h3">Comments</h2>

                    @if(session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    @if($comments->count() > 0)
                        <div class="blog-comment-list">
                            @foreach($comments as $comment)
                                <article class="blog-comment">
                                    <div class="blog-comment__meta">
                                        <strong>{{ $comment->name }}</strong>
                                        <time datetime="{{ $comment->created_at->toDateString() }}">{{ $comment->created_at->format('M j, Y') }}</time>
                                    </div>
                                    <p class="mb-0">{!! nl2br(e($comment->content)) !!}</p>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No comments yet.</p>
                    @endif

                    @if($post->comments_enabled)
                        <h3 class="h4 mt-5">Leave a Comment</h3>
                        <p class="text-muted small">Comments are moderated before appearing publicly.</p>

                        <form action="{{ route('blog.comments.store', $post->slug) }}" method="POST" class="blog-comment-form">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                    <div class="form-text">Your email will not be published.</div>
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="content" class="form-label">Comment <span class="text-danger">*</span></label>
                                    <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="5" maxlength="2000" required>{{ old('content') }}</textarea>
                                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="d-none" aria-hidden="true">
                                <label for="website">Leave this empty</label>
                                <input type="text" id="website" name="website" value="" tabindex="-1" autocomplete="off">
                            </div>

                            <button type="submit" class="btn btn-brand mt-4">Submit Comment</button>
                        </form>
                    @else
                        <p class="text-muted mt-4">Comments are closed for this article.</p>
                    @endif
                </section>

                <div class="mt-5">
                    <a href="{{ route('blog.index') }}" class="small text-muted">&larr; All articles</a>
                </div>
            </div>
        </div>
    </div>
</article>
@endsection

@section('structured_data')
    <x-structured-data :data="app(\App\Services\StructuredDataService::class)->blogPosting($post)" />
@endsection
