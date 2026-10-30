@extends('admin.layouts.app')

@section('title', 'Edit: ' . $testimonial->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Testimonial</h1>
    <div>
        <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline-secondary">Back to Testimonials</a>
    </div>
</div>

<form action="{{ route('admin.testimonials.update', $testimonial) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.testimonials._form', ['testimonial' => $testimonial])
</form>
@endsection
