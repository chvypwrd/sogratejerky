@extends('admin.layouts.app')

@section('title', 'Create Blog Category')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Blog Category</h1>
    <a href="{{ route('admin.blog.categories.index') }}" class="btn btn-outline-secondary">Back to Categories</a>
</div>

<form action="{{ route('admin.blog.categories.store') }}" method="POST">
    @csrf
    @include('admin.blog.categories._form', ['category' => $category])
</form>
@endsection
