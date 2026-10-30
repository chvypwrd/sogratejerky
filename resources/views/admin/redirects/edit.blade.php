@extends('admin.layouts.app')

@section('title', 'Edit Redirect')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Redirect</h1>
    <a href="{{ route('admin.redirects.index') }}" class="btn btn-outline-secondary">Back to Redirects</a>
</div>

<form action="{{ route('admin.redirects.update', $redirect) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.redirects._form', ['redirect' => $redirect])
</form>
@endsection
