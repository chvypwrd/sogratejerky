@extends('layouts.app')

@section('title', 'Something Went Wrong')

@section('content')
<div class="container mx-auto px-4 py-16 text-center">
    <h1 class="text-4xl font-bold mb-4">Something Went Wrong</h1>
    <p class="text-lg text-gray-600 mb-8">We're sorry, but something went wrong. Please try again later.</p>
    <a href="{{ url('/') }}" class="bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition">Back to Home</a>
</div>
@endsection
