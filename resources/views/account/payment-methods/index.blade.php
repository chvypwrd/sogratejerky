@extends('layouts.app')

@section('title', 'Payment Methods')

@section('meta')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Account</span>
            <h1>Payment Methods</h1>
            <p class="lead text-muted">Manage cards saved securely with Stripe.</p>
        </header>

        @include('account._nav')

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        @if($error)
            <div class="alert alert-warning" role="status">{{ $error }}</div>
        @endif

        @if(empty($methods))
            <div class="empty-state">
                <p class="mb-3">You do not have any saved payment methods.</p>
                <a href="{{ route('shop.index') }}" class="btn btn-brand">Continue Shopping</a>
            </div>
        @else
            <div class="card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Card</th>
                                <th>Expires</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($methods as $method)
                                <tr>
                                    <td class="text-capitalize">{{ $method['brand'] }} ending in {{ $method['last4'] }}</td>
                                    <td>{{ $method['exp_month'] }}/{{ $method['exp_year'] }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('account.payment-methods.destroy', $method['id']) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this saved payment method?')">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
