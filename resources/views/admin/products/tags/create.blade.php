@extends('admin.layouts.app')

@section('title', 'Create Product Tag')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Product Tag</h1>
    <a href="{{ route('admin.product-tags.index') }}" class="btn btn-outline-secondary">Back to Tags</a>
</div>

<form action="{{ route('admin.product-tags.store') }}" method="POST">
    @csrf
    @include('admin.products.tags._form', ['tag' => $tag])
</form>
@endsection
