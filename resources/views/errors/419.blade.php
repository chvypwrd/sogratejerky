@extends('layouts.app')

@section('title', 'Session Expired')

@section('content')
<section class="error-page">
    <div class="container">
        <span class="eyebrow">SOGRATE</span>
        <h1>Session Expired</h1>
        <p class="lead">Your session expired. Please refresh and try again.</p>
        <div class="d-flex flex-wrap gap-3 justify-content-center">
            <a href="{{ url('/') }}" class="btn btn-brand">Back to Home</a>
            <a href="{{ route('login') }}" class="btn btn-brand-outline">Login</a>
        </div>
    </div>
</section>
@endsection
