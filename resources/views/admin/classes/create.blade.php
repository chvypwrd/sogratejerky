@extends('admin.layouts.app')

@section('title', 'Create Class Offering')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Class Offering</h1>
    <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">Back to Classes</a>
</div>

<form action="{{ route('admin.classes.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('admin.classes._form', ['class' => $class])
</form>
@endsection
