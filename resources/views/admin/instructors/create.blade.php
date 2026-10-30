@extends('admin.layouts.app')

@section('title', 'Create Instructor')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Instructor</h1>
    <a href="{{ route('admin.instructors.index') }}" class="btn btn-outline-secondary">Back to Instructors</a>
</div>

<form action="{{ route('admin.instructors.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('admin.instructors._form', ['instructor' => $instructor])
</form>
@endsection
