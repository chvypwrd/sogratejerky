@extends('admin.layouts.app')

@section('title', 'Edit: ' . $product->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Product</h1>
    <div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Back to Products</a>
        @if($product->is_active)
            <a href="{{ route('shop.show', ['product' => $product->slug]) }}" class="btn btn-outline-primary" target="_blank">View Product</a>
        @endif
    </div>
</div>

<form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.products._form', ['product' => $product])
</form>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Variants</span>
        @can('products.update')
            <a href="{{ route('admin.products.variants.create', $product) }}" class="btn btn-sm btn-primary">Add Variant</a>
        @endcan
    </div>
    <div class="card-body">
        @if($product->variants->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Weight</th>
                            <th>Status</th>
                            <th>Shareable URL</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->variants->sortBy([['sort_order', 'asc'], ['name', 'asc']]) as $variant)
                            <tr>
                                <td class="fw-medium">{{ $variant->name }}</td>
                                <td>{{ $variant->sku }}</td>
                                <td>{{ $variant->price_formatted }}</td>
                                <td>{{ $variant->weight_display }}</td>
                                <td><span class="badge bg-{{ $variant->is_active ? 'success' : 'secondary' }}">{{ $variant->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>
                                    <a href="{{ route('shop.show', ['product' => $product->slug, 'variant' => $variant->id]) }}" target="_blank" class="small">
                                        Open variant
                                    </a>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.products.variants.edit', [$product, $variant]) }}" class="btn btn-outline-secondary">Edit</a>
                                        <form action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this variant?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">No variants yet. Add at least one active variant before the product can appear in the shop.</p>
        @endif
    </div>
</div>
@endsection
