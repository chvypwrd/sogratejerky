@extends('admin.layouts.app')

@section('title', 'Create Testimonial')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Testimonial</h1>
    <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline-secondary">Back to Testimonials</a>
</div>

<form action="{{ route('admin.testimonials.store') }}" method="POST">
    @csrf
    @include('admin.testimonials._form', ['testimonial' => $testimonial])
</form>
@endsection
