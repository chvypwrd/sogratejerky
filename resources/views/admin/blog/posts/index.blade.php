@extends('admin.layouts.app')

@section('title', 'Blog Posts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Blog Posts</h1>
    @can('blog.posts.create')
        <a href="{{ route('admin.blog.posts.create') }}" class="btn btn-primary">Create Post</a>
    @endcan
</div>

<ul class="nav nav-tabs mb-4">
    @foreach(['all' => 'All', 'draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published'] as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $filter === $key ? 'active' : '' }}" href="{{ route('admin.blog.posts.index', ['filter' => $key]) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="card">
    <div class="card-body">
        @if($posts->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Author</th>
                            <th>Published At</th>
                            <th>Comments</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($posts as $post)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.blog.posts.edit', $post) }}" class="text-decoration-none fw-medium">{{ $post->title }}</a>
                                    <div class="text-muted small">/blog/{{ $post->slug }}</div>
                                </td>
                                <td><span class="badge bg-{{ $post->status->color() }}">{{ $post->status->label() }}</span></td>
                                <td>{{ $post->author_display_name }}</td>
                                <td>{{ $post->published_at?->format('M d, Y H:i') ?? '-' }}</td>
                                <td>{{ $post->comments_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.blog.posts.edit', $post) }}" class="btn btn-outline-secondary">Edit</a>
                                        @if($post->status->value === 'published')
                                            <form action="{{ route('admin.blog.posts.unpublish', $post) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning">Unpublish</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.blog.posts.publish', $post) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success">Publish</button>
                                            </form>
                                        @endif
                                        @can('blog.posts.delete')
                                            <form action="{{ route('admin.blog.posts.destroy', $post) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this post?')">Delete</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $posts->links() }}
        @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">No blog posts found.</p>
                @can('blog.posts.create')
                    <a href="{{ route('admin.blog.posts.create') }}" class="btn btn-primary">Create your first post</a>
                @endcan
            </div>
        @endif
    </div>
</div>
@endsection
