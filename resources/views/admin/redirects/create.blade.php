@extends('admin.layouts.app')

@section('title', 'Create Redirect')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Redirect</h1>
    <a href="{{ route('admin.redirects.index') }}" class="btn btn-outline-secondary">Back to Redirects</a>
</div>

<form action="{{ route('admin.redirects.store') }}" method="POST">
    @csrf
    @include('admin.redirects._form', ['redirect' => $redirect])
</form>
@endsection
