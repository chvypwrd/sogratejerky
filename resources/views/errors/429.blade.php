@extends('layouts.app')

@section('title', 'Too Many Requests')

@section('content')
<section class="error-page">
    <div class="container">
        <span class="eyebrow">Sograte</span>
        <h1>Too Many Requests</h1>
        <p class="lead">You've made too many requests. Please wait a moment before trying again.</p>
        <a href="{{ url('/') }}" class="btn btn-brand">Back to Home</a>
    </div>
</section>
@endsection
