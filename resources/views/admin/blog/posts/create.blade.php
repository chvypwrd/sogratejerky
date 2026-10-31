@extends('admin.layouts.app')

@section('title', 'Create Blog Post')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Blog Post</h1>
    <a href="{{ route('admin.blog.posts.index') }}" class="btn btn-outline-secondary">Back to Posts</a>
</div>

<form action="{{ route('admin.blog.posts.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('admin.blog.posts._form', ['post' => $post])
</form>
@endsection
