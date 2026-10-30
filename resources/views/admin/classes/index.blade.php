@extends('admin.layouts.app')

@section('title', 'Class Offerings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Class Offerings</h1>
    @can('classes.create')
        <a href="{{ route('admin.classes.create') }}" class="btn btn-primary">Create Class</a>
    @endcan
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('admin.classes.index', ['filter' => 'all']) }}">All</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'active' ? 'active' : '' }}" href="{{ route('admin.classes.index', ['filter' => 'active']) }}">Active</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'inactive' ? 'active' : '' }}" href="{{ route('admin.classes.index', ['filter' => 'inactive']) }}">Inactive</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'trash' ? 'active' : '' }}" href="{{ route('admin.classes.index', ['filter' => 'trash']) }}">Trash</a>
    </li>
</ul>

<div class="card">
    <div class="card-body">
        @if($classes->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Media</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classes as $class)
                            <tr>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="text-decoration-none fw-medium text-muted">{{ $class->title }}</span>
                                    @else
                                        <a href="{{ route('admin.classes.edit', $class) }}" class="text-decoration-none fw-medium">
                                            {{ $class->title }}
                                        </a>
                                    @endif
                                </td>
                                <td>{{ $class->level ?? '—' }}</td>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="badge bg-danger">Trashed</span>
                                    @else
                                        <span class="badge bg-{{ $class->active ? 'success' : 'secondary' }}">
                                            {{ $class->active ? 'Active' : 'Inactive' }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $class->featured ? 'Yes' : 'No' }}</td>
                                <td>{{ $class->media_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if($filter === 'trash')
                                            <form action="{{ route('admin.classes.restore', $class) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success">Restore</button>
                                            </form>
                                            <form action="{{ route('admin.classes.force-delete', $class) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Permanently delete this class offering?')">Delete Permanently</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.classes.edit', $class) }}" class="btn btn-outline-secondary">Edit</a>
                                            <form action="{{ route('admin.classes.destroy', $class) }}" method="POST" class="d-inline">
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

            {{ $classes->links() }}
        @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">
                    @if($filter === 'trash')
                        No trashed class offerings.
                    @else
                        No class offerings found.
                    @endif
                </p>
                @if($filter !== 'trash')
                    @can('classes.create')
                        <a href="{{ route('admin.classes.create') }}" class="btn btn-primary">Create your first class offering</a>
                    @endcan
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
