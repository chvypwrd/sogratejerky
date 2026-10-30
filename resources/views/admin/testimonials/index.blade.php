@extends('admin.layouts.app')

@section('title', 'Testimonials')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Testimonials</h1>
    @can('testimonials.create')
        <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary">Create Testimonial</a>
    @endcan
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('admin.testimonials.index', ['filter' => 'all']) }}">All</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'active' ? 'active' : '' }}" href="{{ route('admin.testimonials.index', ['filter' => 'active']) }}">Active</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'inactive' ? 'active' : '' }}" href="{{ route('admin.testimonials.index', ['filter' => 'inactive']) }}">Inactive</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'trash' ? 'active' : '' }}" href="{{ route('admin.testimonials.index', ['filter' => 'trash']) }}">Trash</a>
    </li>
</ul>

<div class="card">
    <div class="card-body">
        @if($testimonials->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Quote</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($testimonials as $testimonial)
                            <tr>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="text-decoration-none fw-medium text-muted">{{ $testimonial->name }}</span>
                                    @else
                                        <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="text-decoration-none fw-medium">
                                            {{ $testimonial->name }}
                                        </a>
                                    @endif
                                </td>
                                <td>{{ Str::limit($testimonial->quote, 60) }}</td>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="badge bg-danger">Trashed</span>
                                    @else
                                        <span class="badge bg-{{ $testimonial->active ? 'success' : 'secondary' }}">
                                            {{ $testimonial->active ? 'Active' : 'Inactive' }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $testimonial->featured ? 'Yes' : 'No' }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if($filter === 'trash')
                                            <form action="{{ route('admin.testimonials.restore', $testimonial) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success">Restore</button>
                                            </form>
                                            <form action="{{ route('admin.testimonials.force-delete', $testimonial) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Permanently delete this testimonial?')">Delete Permanently</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="btn btn-outline-secondary">Edit</a>
                                            <form action="{{ route('admin.testimonials.destroy', $testimonial) }}" method="POST" class="d-inline">
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

            {{ $testimonials->links() }}
        @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">
                    @if($filter === 'trash')
                        No trashed testimonials.
                    @else
                        No testimonials found.
                    @endif
                </p>
                @if($filter !== 'trash')
                    @can('testimonials.create')
                        <a href="{{ route('admin.testimonials.create') }}" class="btn btn-primary">Create your first testimonial</a>
                    @endcan
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
