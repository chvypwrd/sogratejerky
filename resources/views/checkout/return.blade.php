@extends('layouts.app')

@section('title', 'Payment Status')

@section('meta')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Payment</span>
            <h1>Payment Status</h1>
            <p class="lead text-muted">Order {{ $order->order_number }}</p>
        </header>

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if($error)
            <div class="alert alert-warning" role="status">{{ $error }}</div>
        @endif

        @if($payment)
            @if($payment->status === \App\Enums\PaymentStatus::Paid)
                <div class="alert alert-success" role="status">
                    Payment confirmed. Your order is placed and ready for processing.
                </div>
            @elseif($payment->status === \App\Enums\PaymentStatus::Processing)
                <div class="alert alert-info" role="status">
                    Your payment is still processing. This page will update when Stripe confirms the payment.
                </div>
            @elseif(in_array($payment->status, [\App\Enums\PaymentStatus::PartiallyRefunded, \App\Enums\PaymentStatus::Refunded], true))
                <div class="alert alert-secondary" role="status">
                    Your payment has been refunded.
                </div>
            @else
                <div class="alert alert-danger" role="alert">
                    Payment was not completed. Your order has not been fulfilled.
                </div>
            @endif
        @endif

        @include('checkout._order-details')

        <div class="d-flex flex-wrap gap-2 mt-4">
            @if($payment && $payment->status === \App\Enums\PaymentStatus::Paid)
                <a href="{{ $confirmationUrl }}" class="btn btn-brand">View Order Confirmation</a>
            @elseif($payment && $payment->status === \App\Enums\PaymentStatus::Processing)
                <a href="{{ $confirmationUrl }}" class="btn btn-brand-outline">View Order</a>
            @elseif(! $error)
                <a href="{{ route('checkout.index') }}" class="btn btn-brand">Return to Checkout</a>
            @endif
            @auth
                <a href="{{ route('account.orders.index') }}" class="btn btn-brand-outline">View My Orders</a>
            @endauth
            <a href="{{ route('shop.index') }}" class="btn btn-brand-outline">Continue Shopping</a>
        </div>
    </div>
</section>
@endsection
