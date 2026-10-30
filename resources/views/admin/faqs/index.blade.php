@extends('admin.layouts.app')

@section('title', 'FAQs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">FAQs</h1>
    @can('faqs.create')
        <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary">Create FAQ</a>
    @endcan
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('admin.faqs.index', ['filter' => 'all']) }}">All</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'active' ? 'active' : '' }}" href="{{ route('admin.faqs.index', ['filter' => 'active']) }}">Active</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'inactive' ? 'active' : '' }}" href="{{ route('admin.faqs.index', ['filter' => 'inactive']) }}">Inactive</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'trash' ? 'active' : '' }}" href="{{ route('admin.faqs.index', ['filter' => 'trash']) }}">Trash</a>
    </li>
</ul>

<div class="card">
    <div class="card-body">
        @if($faqs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faqs as $faq)
                            <tr>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="text-decoration-none fw-medium text-muted">{{ $faq->question }}</span>
                                    @else
                                        <a href="{{ route('admin.faqs.edit', $faq) }}" class="text-decoration-none fw-medium">
                                            {{ $faq->question }}
                                        </a>
                                    @endif
                                </td>
                                <td>{{ $faq->category ?? '—' }}</td>
                                <td>
                                    @if($filter === 'trash')
                                        <span class="badge bg-danger">Trashed</span>
                                    @else
                                        <span class="badge bg-{{ $faq->active ? 'success' : 'secondary' }}">
                                            {{ $faq->active ? 'Active' : 'Inactive' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if($filter === 'trash')
                                            <form action="{{ route('admin.faqs.restore', $faq) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success">Restore</button>
                                            </form>
                                            <form action="{{ route('admin.faqs.force-delete', $faq) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Permanently delete this FAQ?')">Delete Permanently</button>
                                            </form>
                                        @else
                                            <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-outline-secondary">Edit</a>
                                            <form action="{{ route('admin.faqs.destroy', $faq) }}" method="POST" class="d-inline">
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

            {{ $faqs->links() }}
        @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">
                    @if($filter === 'trash')
                        No trashed FAQs.
                    @else
                        No FAQs found.
                    @endif
                </p>
                @if($filter !== 'trash')
                    @can('faqs.create')
                        <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary">Create your first FAQ</a>
                    @endcan
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
