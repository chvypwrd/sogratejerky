@extends('admin.layouts.app')

@section('title', 'Blog Comments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Blog Comments</h1>
</div>

<ul class="nav nav-tabs mb-3">
    @foreach($statuses as $status)
        <li class="nav-item">
            <a class="nav-link {{ $filter === $status->value ? 'active' : '' }}"
               href="{{ route('admin.blog.comments.index', ['filter' => $status->value, 'post_id' => $postId]) }}">
                {{ $status->label() }}
            </a>
        </li>
    @endforeach
    <li class="nav-item">
        <a class="nav-link {{ ! in_array($filter, $statusValues, true) ? 'active' : '' }}"
           href="{{ route('admin.blog.comments.index', ['filter' => 'all', 'post_id' => $postId]) }}">
            All
        </a>
    </li>
</ul>

<form method="GET" action="{{ route('admin.blog.comments.index') }}" class="row g-2 align-items-end mb-3">
    <input type="hidden" name="filter" value="{{ $filter }}">
    <div class="col-md-5">
        <label for="post_id" class="form-label">Filter by post</label>
        <select class="form-select" id="post_id" name="post_id">
            <option value="">All posts</option>
            @foreach($posts as $post)
                <option value="{{ $post->id }}" {{ (int) $postId === $post->id ? 'selected' : '' }}>{{ $post->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary">Apply</button>
    </div>
</form>

<div class="card">
    <div class="card-body">
        @if($comments->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Comment</th>
                            <th>Post</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comments as $comment)
                            <tr>
                                <td style="max-width: 420px;">
                                    <div class="fw-medium">{{ $comment->name }}</div>
                                    <div class="text-muted small mb-1">{{ $comment->email }}</div>
                                    <div>{{ $comment->content }}</div>
                                </td>
                                <td>{{ $comment->post?->title ?? 'Deleted post' }}</td>
                                <td><span class="badge bg-{{ $comment->status->color() }}">{{ $comment->status->label() }}</span></td>
                                <td>{{ $comment->created_at->format('M d, Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if($comment->status->value !== 'approved')
                                            <form action="{{ route('admin.blog.comments.approve', $comment) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success">Approve</button>
                                            </form>
                                        @endif
                                        @if($comment->status->value !== 'rejected')
                                            <form action="{{ route('admin.blog.comments.reject', $comment) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning">Reject</button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.blog.comments.destroy', $comment) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this comment?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $comments->links() }}
        @else
            <p class="text-muted mb-0">No comments found.</p>
        @endif
    </div>
</div>
@endsection
