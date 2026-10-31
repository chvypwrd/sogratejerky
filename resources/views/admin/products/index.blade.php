@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Products</h1>
    @can('products.create')
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Create Product</a>
    @endcan
</div>

<ul class="nav nav-tabs mb-4">
    @foreach(['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'featured' => 'Featured'] as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $filter === $key ? 'active' : '' }}" href="{{ route('admin.products.index', ['filter' => $key]) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="card">
    <div class="card-body">
        @if($products->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Status</th>
                            <th>Variants</th>
                            <th>Starting Price</th>
                            <th>Categories</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="text-decoration-none fw-medium">{{ $product->name }}</a>
                                    <div class="text-muted small">/shop/{{ $product->slug }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                                    @if($product->is_featured)
                                        <span class="badge bg-warning ms-1">Featured</span>
                                    @endif
                                </td>
                                <td>{{ $product->available_variants_count }} active / {{ $product->variants_count }}</td>
                                <td>{{ $product->starting_price_formatted ?? '-' }}</td>
                                <td>{{ $product->categories->pluck('name')->join(', ') ?: '-' }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary">Edit</a>
                                        @if($product->is_active)
                                            <a href="{{ route('shop.show', ['product' => $product->slug]) }}" class="btn btn-outline-primary" target="_blank">View</a>
                                        @endif
                                        @can('products.delete')
                                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this product and its variants?')">Delete</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $products->links() }}
        @else
            <div class="text-center py-5">
                <p class="text-muted mb-3">No products found.</p>
                @can('products.create')
                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Create your first product</a>
                @endcan
            </div>
        @endif
    </div>
</div>
@endsection
