@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
<div class="container mx-auto px-4 py-16 text-center">
    <h1 class="text-4xl font-bold mb-4">Page Not Found</h1>
    <p class="text-lg text-gray-600 mb-8">The page you're looking for doesn't exist or has been moved.</p>
    <div class="flex justify-center gap-4">
        <a href="{{ url('/') }}" class="bg-red-600 text-white px-6 py-3 rounded hover:bg-red-700 transition">Home</a>
        <a href="{{ route('contact.show') }}" class="bg-gray-800 text-white px-6 py-3 rounded hover:bg-gray-900 transition">Contact</a>
    </div>
</div>
@endsection
