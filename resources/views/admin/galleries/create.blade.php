@extends('admin.layouts.app')

@section('title', 'Create Gallery')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Gallery</h1>
    <a href="{{ route('admin.galleries.index') }}" class="btn btn-outline-secondary">Back to Galleries</a>
</div>

<form action="{{ route('admin.galleries.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('admin.galleries._form', ['gallery' => $gallery])
</form>
@endsection
