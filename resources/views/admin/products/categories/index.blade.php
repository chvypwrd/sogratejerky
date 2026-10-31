@extends('admin.layouts.app')

@section('title', 'Product Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Product Categories</h1>
    <a href="{{ route('admin.product-categories.create') }}" class="btn btn-primary">Create Category</a>
</div>

<div class="card">
    <div class="card-body">
        @if($categories->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.product-categories.edit', $category) }}" class="text-decoration-none fw-medium">{{ $category->name }}</a>
                                    @if($category->description)
                                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit($category->description, 120) }}</div>
                                    @endif
                                </td>
                                <td>/shop/category/{{ $category->slug }}</td>
                                <td>{{ $category->products_count }}</td>
                                <td><span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.product-categories.edit', $category) }}" class="btn btn-outline-secondary">Edit</a>
                                        <form action="{{ route('admin.product-categories.destroy', $category) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this category?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $categories->links() }}
        @else
            <p class="text-muted mb-0">No product categories yet.</p>
        @endif
    </div>
</div>
@endsection
