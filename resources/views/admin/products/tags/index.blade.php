@extends('admin.layouts.app')

@section('title', 'Product Tags')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Product Tags</h1>
    <a href="{{ route('admin.product-tags.create') }}" class="btn btn-primary">Create Tag</a>
</div>

<div class="card">
    <div class="card-body">
        @if($tags->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Products</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tags as $tag)
                            <tr>
                                <td><a href="{{ route('admin.product-tags.edit', $tag) }}" class="text-decoration-none fw-medium">{{ $tag->name }}</a></td>
                                <td>/shop/tag/{{ $tag->slug }}</td>
                                <td>{{ $tag->products_count }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.product-tags.edit', $tag) }}" class="btn btn-outline-secondary">Edit</a>
                                        <form action="{{ route('admin.product-tags.destroy', $tag) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this tag?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $tags->links() }}
        @else
            <p class="text-muted mb-0">No product tags yet.</p>
        @endif
    </div>
</div>
@endsection
