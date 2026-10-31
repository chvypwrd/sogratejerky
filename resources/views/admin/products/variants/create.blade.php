@extends('admin.layouts.app')

@section('title', 'Add Variant: ' . $product->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Add Variant</h1>
    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary">Back to Product</a>
</div>

<form action="{{ route('admin.products.variants.store', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('admin.products.variants._form', ['product' => $product, 'variant' => $variant])
</form>
@endsection
