@extends('admin.layouts.app')

@section('title', 'Instructors')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Instructors</h1>
    @can('instructors.create')
        <a href="{{ route('admin.instructors.create') }}" class="btn btn-primary">Create Instructor</a>
    @endcan
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('admin.instructors.index', ['filter' => 'all']) }}">All</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'active' ? 'active' : '' }}" href="{{ route('admin.instructors.index', ['filter' => 'active']) }}">Active</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'inactive' ? 'active' : '' }}" href="{{ route('admin.instructors.index', ['filter' => 'inactive']) }}">Inactive</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'trash' ? 'active' : '' }}" href="{{ route('admin.instructors.index', ['filter' => 'trash']) }}">Trash</a>
    </li>
</ul>

<div class="card">
    <div class="card-body">
        @if($instructors->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Media</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($instructors as $instructor)
                            <tr>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="text-decoration-none fw-medium text-muted">{{ $instructor->name }}</span>
                                    @else
                                        <a href="{{ route('admin.instructors.edit', $instructor) }}" class="text-decoration-none fw-medium">
                                            {{ $instructor->name }}
                                        </a>
                                    @endif
                                </td>
                                <td>{{ $instructor->title ?? '—' }}</td>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="badge bg-danger">Trashed</span>
                                    @else
                                        <span class="badge bg-{{ $instructor->active ? 'success' : 'secondary' }}">
                                            {{ $instructor->active ? 'Active' : 'Inactive' }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $instructor->media_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if($filter === 'trash')
                                            <form action="{{ route('admin.instructors.restore', $instructor) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success">Restore</button>
                                            </form>
                                            <form action="{{ route('admin.instructors.force-delete', $instructor) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Permanently delete this instructor?')">Delete Permanently</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.instructors.edit', $instructor) }}" class="btn btn-outline-secondary">Edit</a>
                                            <form action="{{ route('admin.instructors.destroy', $instructor) }}" method="POST" class="d-inline">
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

            {{ $instructors->links() }}
        @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">
                    @if($filter === 'trash')
                        No trashed instructors.
                    @else
                        No instructors found.
                    @endif
                </p>
                @if($filter !== 'trash')
                    @can('instructors.create')
                        <a href="{{ route('admin.instructors.create') }}" class="btn btn-primary">Create your first instructor</a>
                    @endcan
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
