@extends('admin.layouts.app')

@section('title', 'Redirects')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Redirects</h1>
    @can('redirects.manage')
        <a href="{{ route('admin.redirects.create') }}" class="btn btn-primary">Create Redirect</a>
    @endcan
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('admin.redirects.index', ['filter' => 'all']) }}">All</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'active' ? 'active' : '' }}" href="{{ route('admin.redirects.index', ['filter' => 'active']) }}">Active</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'inactive' ? 'active' : '' }}" href="{{ route('admin.redirects.index', ['filter' => 'inactive']) }}">Inactive</a>
    </li>
</ul>

<div class="card">
    <div class="card-body">
        @if($redirects->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Old Path</th>
                            <th>New Path</th>
                            <th>Status</th>
                            <th>Active</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($redirects as $redirect)
                            <tr>
                                <td><code>{{ $redirect->old_path }}</code></td>
                                <td><code>{{ $redirect->new_path }}</code></td>
                                <td><span class="badge bg-info">{{ $redirect->status_code }}</span></td>
                                <td>
                                    <span class="badge bg-{{ $redirect->active ? 'success' : 'secondary' }}">
                                        {{ $redirect->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @can('redirects.manage')
                                            <a href="{{ route('admin.redirects.edit', $redirect) }}" class="btn btn-outline-secondary">Edit</a>
                                            <form action="{{ route('admin.redirects.destroy', $redirect) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this redirect?')">Delete</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $redirects->links() }}
        @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">No redirects found.</p>
                @can('redirects.manage')
                    <a href="{{ route('admin.redirects.create') }}" class="btn btn-primary">Create your first redirect</a>
                @endcan
            </div>
        @endif
    </div>
</div>
@endsection
