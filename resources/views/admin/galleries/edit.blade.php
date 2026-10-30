@extends('admin.layouts.app')

@section('title', 'Edit: ' . $gallery->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Gallery</h1>
    <div>
        <a href="{{ route('admin.galleries.index') }}" class="btn btn-outline-secondary">Back to Galleries</a>
    </div>
</div>

<form action="{{ route('admin.galleries.update', $gallery) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.galleries._form', ['gallery' => $gallery])
</form>
@endsection
