@extends('admin.layouts.app')

@section('title', 'Create Blog Tag')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Blog Tag</h1>
    <a href="{{ route('admin.blog.tags.index') }}" class="btn btn-outline-secondary">Back to Tags</a>
</div>

<form action="{{ route('admin.blog.tags.store') }}" method="POST">
    @csrf
    @include('admin.blog.tags._form', ['tag' => $tag])
</form>
@endsection
