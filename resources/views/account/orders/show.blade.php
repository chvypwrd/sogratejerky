@extends('layouts.app')

@section('title', 'Order '.$order->order_number)

@section('meta')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Account</span>
            <h1>Order Details</h1>
            <p class="lead text-muted">Order {{ $order->order_number }}</p>
        </header>

        @include('account._nav')

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @include('checkout._order-details')

        <a href="{{ route('account.orders.index') }}" class="btn btn-brand-outline mt-4">Back to Order History</a>
    </div>
</section>
@endsection
