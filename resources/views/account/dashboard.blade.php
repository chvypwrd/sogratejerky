@extends('layouts.app')

@section('title', 'My Account')

@section('meta')
    <meta name="robots" content="noindex,follow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Account</span>
            <h1>My Account</h1>
            <p class="lead text-muted">Welcome back, {{ $user->name }}.</p>
        </header>

        @include('account._nav')

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if(! $user->hasVerifiedEmail())
            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Your email address is not verified yet.</span>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-dark">Resend Verification Email</button>
                </form>
            </div>
        @endif

        <div class="row g-4 account-dashboard">
            <div class="col-md-6 col-lg-3">
                <div class="account-card">
                    <h2>Profile</h2>
                    <p>{{ $user->name }}<br>{{ $user->email }}</p>
                    <a href="{{ route('account.profile.edit') }}" class="btn btn-brand-outline btn-sm">Manage Profile</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="account-card">
                    <h2>Addresses</h2>
                    <p>{{ $addressCount }} saved {{ \Illuminate\Support\Str::plural('address', $addressCount) }}</p>
                    <a href="{{ route('account.addresses.index') }}" class="btn btn-brand-outline btn-sm">Manage Addresses</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="account-card">
                    <h2>Cart</h2>
                    <p>{{ $cartItemCount }} {{ \Illuminate\Support\Str::plural('item', $cartItemCount) }} in your cart</p>
                    <a href="{{ route('cart.index') }}" class="btn btn-brand-outline btn-sm">View Cart</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="account-card">
                    <h2>Order History</h2>
                    <p>Review orders placed with your account.</p>
                    <a href="{{ route('account.orders.index') }}" class="btn btn-brand-outline btn-sm">View Orders</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
