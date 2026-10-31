@extends('admin.layouts.app')

@section('title', 'Edit Variant: ' . $variant->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Variant</h1>
    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary">Back to Product</a>
</div>

<form action="{{ route('admin.products.variants.update', [$product, $variant]) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.products.variants._form', ['product' => $product, 'variant' => $variant])
</form>
@endsection
