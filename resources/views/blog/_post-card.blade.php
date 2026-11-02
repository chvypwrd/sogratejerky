@php
    $media = $post->getFirstMedia('featured');
    $imageUrl = $media?->getUrl('card');
    $altText = $media?->getCustomProperty('alt_text', $post->title) ?: $post->title;
    $excerpt = $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->content), 160);
@endphp

<article class="blog-card card h-100">
    <a href="{{ route('blog.show', $post->slug) }}" class="blog-card__media">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ e($altText) }}" loading="lazy">
        @else
            <span class="blog-card__placeholder">{{ config('app.name', 'Sograte') }}</span>
        @endif
    </a>

    <div class="card-body">
        <div class="blog-meta">
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('M j, Y') }}</time>
            <span aria-hidden="true">&middot;</span>
            <span>{{ $post->author_display_name }}</span>
        </div>

        @if($post->categories->isNotEmpty())
            <div class="blog-categories">
                @foreach($post->categories as $category)
                    <a href="{{ route('blog.category', $category->slug) }}">{{ $category->name }}</a>
                @endforeach
            </div>
        @endif

        <h2 class="blog-card__title">
            <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
        </h2>

        @if($excerpt)
            <p class="card-text">{{ $excerpt }}</p>
        @endif

        <a href="{{ route('blog.show', $post->slug) }}" class="blog-card__link">
            Read More <span aria-hidden="true">&rarr;</span>
        </a>
    </div>
</article>
