@extends('admin.layouts.app')

@section('title', 'Galleries')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Galleries</h1>
    @can('galleries.create')
        <a href="{{ route('admin.galleries.create') }}" class="btn btn-primary">Create Gallery</a>
    @endcan
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('admin.galleries.index', ['filter' => 'all']) }}">All</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'active' ? 'active' : '' }}" href="{{ route('admin.galleries.index', ['filter' => 'active']) }}">Active</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'inactive' ? 'active' : '' }}" href="{{ route('admin.galleries.index', ['filter' => 'inactive']) }}">Inactive</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'trash' ? 'active' : '' }}" href="{{ route('admin.galleries.index', ['filter' => 'trash']) }}">Trash</a>
    </li>
</ul>

<div class="card">
    <div class="card-body">
        @if($galleries->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Photos</th>
                            <th>Active</th>
                            <th>Featured</th>
                            <th>Sort Order</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($galleries as $gallery)
                            <tr>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="text-decoration-none fw-medium text-muted">{{ $gallery->title }}</span>
                                    @else
                                        <a href="{{ route('admin.galleries.edit', $gallery) }}" class="text-decoration-none fw-medium">
                                            {{ $gallery->title }}
                                        </a>
                                    @endif
                                </td>
                                <td>{{ $gallery->media_count }}</td>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="badge bg-danger">Trashed</span>
                                    @else
                                        <span class="badge bg-{{ $gallery->active ? 'success' : 'secondary' }}">
                                            {{ $gallery->active ? 'Active' : 'Inactive' }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $gallery->featured ? 'Yes' : 'No' }}</td>
                                <td>{{ $gallery->sort_order }}</td>
                                <td>{{ $gallery->updated_at->diffForHumans() }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if($filter === 'trash')
                                            <form action="{{ route('admin.galleries.restore', $gallery) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success">Restore</button>
                                            </form>
                                            <form action="{{ route('admin.galleries.force-delete', $gallery) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Permanently delete this gallery?')">Delete Permanently</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.galleries.edit', $gallery) }}" class="btn btn-outline-secondary">Edit</a>
                                            <form action="{{ route('admin.galleries.destroy', $gallery) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger">Trash</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $galleries->links() }}
        @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">
                    @if($filter === 'trash')
                        No trashed galleries.
                    @else
                        No galleries found.
                    @endif
                </p>
                @if($filter !== 'trash')
                    @can('galleries.create')
                        <a href="{{ route('admin.galleries.create') }}" class="btn btn-primary">Create your first gallery</a>
                    @endcan
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
