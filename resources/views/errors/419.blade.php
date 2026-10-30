@extends('layouts.app')

@section('title', 'Session Expired')

@section('content')
<div class="container mx-auto px-4 py-16 text-center">
    <h1 class="text-4xl font-bold mb-4">Session Expired</h1>
    <p class="text-lg text-gray-600 mb-8">Your session expired. Please refresh and try again.</p>
    <div class="flex justify-center gap-4">
        <a href="{{ url('/') }}" class="bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition">Home</a>
        <a href="{{ route('login') }}" class="bg-gray-800 text-white px-6 py-3 rounded hover:bg-gray-900 transition">Login</a>
    </div>
</div>
@endsection
