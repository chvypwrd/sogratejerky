@extends('admin.layouts.app')

@section('title', 'Edit: ' . $category->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Blog Category</h1>
    <a href="{{ route('admin.blog.categories.index') }}" class="btn btn-outline-secondary">Back to Categories</a>
</div>

<form action="{{ route('admin.blog.categories.update', $category) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.blog.categories._form', ['category' => $category])
</form>
@endsection
