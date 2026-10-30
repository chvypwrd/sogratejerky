@extends('layouts.app')

@section('title', 'Too Many Requests')

@section('content')
<div class="container mx-auto px-4 py-16 text-center">
    <h1 class="text-4xl font-bold mb-4">Too Many Requests</h1>
    <p class="text-lg text-gray-600 mb-8">You've made too many requests. Please wait a moment before trying again.</p>
    <a href="{{ url('/') }}" class="bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition">Back to Home</a>
</div>
@endsection
