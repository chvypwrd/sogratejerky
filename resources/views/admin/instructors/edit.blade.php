@extends('admin.layouts.app')

@section('title', 'Edit: ' . $instructor->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Instructor</h1>
    <div>
        <a href="{{ route('admin.instructors.index') }}" class="btn btn-outline-secondary">Back to Instructors</a>
    </div>
</div>

<form action="{{ route('admin.instructors.update', $instructor) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.instructors._form', ['instructor' => $instructor])
</form>
@endsection
