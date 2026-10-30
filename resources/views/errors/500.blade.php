@extends('layouts.app')

@section('title', 'Something Went Wrong')

@section('content')
<section class="error-page">
    <div class="container">
        <span class="eyebrow">SOGRATE</span>
        <h1>Something Went Wrong</h1>
        <p class="lead">We're sorry, but something went wrong. Please try again later.</p>
        <a href="{{ url('/') }}" class="btn btn-brand">Back to Home</a>
    </div>
</section>
@endsection
