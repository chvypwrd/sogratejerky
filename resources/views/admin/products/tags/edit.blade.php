@extends('admin.layouts.app')

@section('title', 'Edit: ' . $tag->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Product Tag</h1>
    <a href="{{ route('admin.product-tags.index') }}" class="btn btn-outline-secondary">Back to Tags</a>
</div>

<form action="{{ route('admin.product-tags.update', $tag) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.products.tags._form', ['tag' => $tag])
</form>
@endsection
