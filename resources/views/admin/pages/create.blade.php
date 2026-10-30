@extends('admin.layouts.app')

@section('title', 'Create Page')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Page</h1>
    <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">Back to Pages</a>
</div>

<form action="{{ route('admin.pages.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('admin.pages._form', ['page' => $page])
</form>
@endsection
