@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
<section class="error-page">
    <div class="container">
        <span class="eyebrow">Sograte</span>
        <h1>Page Not Found</h1>
        <p class="lead">The page you're looking for doesn't exist or has been moved.</p>
        <div class="d-flex flex-wrap gap-3 justify-content-center">
            <a href="{{ url('/') }}" class="btn btn-brand">Back to Home</a>
            <a href="{{ route('contact.show') }}" class="btn btn-brand-outline">Contact Us</a>
        </div>
    </div>
</section>
@endsection
