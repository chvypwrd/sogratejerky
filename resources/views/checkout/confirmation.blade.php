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

        @include('checkout._order-details')

        <div class="d-flex flex-wrap gap-2 mt-4">
            @auth
                <a href="{{ route('account.orders.show', $order) }}" class="btn btn-brand">View in Account</a>
            @endauth
            <a href="{{ route('shop.index') }}" class="btn btn-brand-outline">Continue Shopping</a>
        </div>
    </div>
</section>
@endsection
