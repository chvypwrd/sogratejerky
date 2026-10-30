@extends('admin.layouts.app')

@section('title', 'Pages')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Pages</h1>
    @can('pages.create')
        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">Create Page</a>
    @endcan
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('admin.pages.index', ['filter' => 'all']) }}">All</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'draft' ? 'active' : '' }}" href="{{ route('admin.pages.index', ['filter' => 'draft']) }}">Draft</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'published' ? 'active' : '' }}" href="{{ route('admin.pages.index', ['filter' => 'published']) }}">Published</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'trash' ? 'active' : '' }}" href="{{ route('admin.pages.index', ['filter' => 'trash']) }}">Trash</a>
    </li>
</ul>

<div class="card">
    <div class="card-body">
        @if($pages->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Creator</th>
                            <th>Published At</th>
                            <th>Media</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pages as $page)
                            <tr>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="text-decoration-none fw-medium text-muted">{{ $page->title }}</span>
                                        <div class="text-muted small text-muted">/{{ $page->slug }}</div>
                                    @else
                                        <a href="{{ route('admin.pages.edit', $page) }}" class="text-decoration-none fw-medium">
                                            {{ $page->title }}
                                        </a>
                                        <div class="text-muted small">/{{ $page->slug }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="badge bg-danger">Trashed</span>
                                    @else
                                        <span class="badge bg-{{ $page->status->color() }}">
                                            {{ $page->status->label() }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $page->creator->name ?? '—' }}</td>
                                <td>{{ $page->published_at?->format('M d, Y H:i') ?? '—' }}</td>
                                <td>{{ $page->media_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if($filter === 'trash')
                                            <form action="{{ route('admin.pages.restore', $page) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success">Restore</button>
                                            </form>
                                            <form action="{{ route('admin.pages.force-delete', $page) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Permanently delete this page?')">Delete Permanently</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-outline-secondary">Edit</a>
                                            <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger">Trash</button>
                                            </form>
                                            @if($page->status->isPublished())
                                                <form action="{{ route('admin.pages.unpublish', $page) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-warning">Unpublish</button>
                                                </form>
                                            @elseif($page->status->value === 'draft')
                                                <form action="{{ route('admin.pages.publish', $page) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success">Publish</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $pages->links() }}
        @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">
                    @if($filter === 'trash')
                        No trashed pages.
                    @else
                        No pages found.
                    @endif
                </p>
                @if($filter !== 'trash')
                    @can('pages.create')
                        <a href="{{ route('admin.pages.create') }}" class="btn btn-primary">Create your first page</a>
                    @endcan
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
