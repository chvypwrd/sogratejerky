@extends('layouts.app')

@section('title', 'Order Confirmation')

@section('meta')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Order Received</span>
            <h1>Thank You</h1>
            @if($order->payment_status === \App\Enums\PaymentStatus::Paid)
                <p class="lead text-muted">Your payment is confirmed and your order has been placed.</p>
            @elseif($order->payment_status === \App\Enums\PaymentStatus::Processing)
                <p class="lead text-muted">Your order is recorded and your payment is still processing.</p>
            @else
                <p class="lead text-muted">Your order is recorded. Payment has not been confirmed yet.</p>
            @endif
        </header>

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('checkout._order-details')

        @if($guestAccountAvailable)
            <div class="card mt-4">
                <div class="card-body">
                    <h2 class="h4">Create Your Account</h2>
                    <p class="text-muted">Save this order to a Sograte account using <strong>{{ $order->customer_email }}</strong>. Set a password below to finish creating your account.</p>
                    <form method="POST" action="{{ $guestAccountAction }}" class="row g-3">
                        @csrf
                        <div class="col-md-5">
                            <label for="claim_password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="claim_password" name="password" autocomplete="new-password" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label for="claim_password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="claim_password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-brand w-100">Create Account</button>
                        </div>
                    </form>
                    <p class="small text-muted mt-3 mb-0">Creating an account is optional. This order will remain available through your confirmation link.</p>
                </div>
            </div>
        @elseif($guestAccountExisting)
            <div class="alert alert-info mt-4" role="status">
                An account already exists for {{ $order->customer_email }}.
                <a href="{{ route('customer.login') }}">Sign in</a> or
                <a href="{{ route('customer.password.request') }}">reset your password</a>.
            </div>
        @endif

        <div class="d-flex flex-wrap gap-2 mt-4">
            @auth
                <a href="{{ route('account.orders.show', $order) }}" class="btn btn-brand">View in Account</a>
            @endauth
            <a href="{{ route('shop.index') }}" class="btn btn-brand-outline">Continue Shopping</a>
        </div>
    </div>
</section>
@endsection
