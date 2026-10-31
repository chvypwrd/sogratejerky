<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0">
    <channel>
        <title>{{ config('app.name', 'SOGRATE') }} Blog</title>
        <link>{{ route('blog.index') }}</link>
        <description>Stories, recipes, and updates from SOGRATE.</description>
        <language>{{ str_replace('_', '-', app()->getLocale()) }}</language>
        @foreach($posts as $post)
            <item>
                <title>{{ $post->title }}</title>
                <link>{{ route('blog.show', $post->slug) }}</link>
                <guid isPermaLink="true">{{ route('blog.show', $post->slug) }}</guid>
                @if($post->published_at)
                    <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>
                @endif
                <description>{{ \Illuminate\Support\Str::limit(strip_tags((string) ($post->excerpt ?: $post->content)), 300) }}</description>
            </item>
        @endforeach
    </channel>
</rss>
