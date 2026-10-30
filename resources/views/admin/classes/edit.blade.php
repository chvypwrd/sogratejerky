@extends('admin.layouts.app')

@section('title', 'Edit: ' . $class->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Class Offering</h1>
    <div>
        <a href="{{ route('admin.classes.index') }}" class="btn btn-outline-secondary">Back to Classes</a>
    </div>
</div>

<form action="{{ route('admin.classes.update', $class) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.classes._form', ['class' => $class])
</form>
@endsection
