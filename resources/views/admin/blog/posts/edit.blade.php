@extends('admin.layouts.app')

@section('title', 'Edit: ' . $post->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Blog Post</h1>
    <div>
        <a href="{{ route('admin.blog.posts.index') }}" class="btn btn-outline-secondary">Back to Posts</a>
        @if($post->isPublished())
            <a href="{{ route('blog.show', $post->slug) }}" class="btn btn-outline-primary" target="_blank">View Live</a>
        @endif
    </div>
</div>

<form action="{{ route('admin.blog.posts.update', $post) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.blog.posts._form', ['post' => $post])
</form>
@endsection
