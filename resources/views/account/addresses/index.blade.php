@extends('layouts.app')

@section('title', 'Saved Addresses')

@section('meta')
    <meta name="robots" content="noindex,follow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Account</span>
            <h1>Saved Addresses</h1>
            <p class="lead text-muted">Manage shipping and billing addresses for future checkout.</p>
        </header>

        @include('account._nav')

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Your Addresses</h2>
            <a href="{{ route('account.addresses.create') }}" class="btn btn-brand">Add Address</a>
        </div>

        @if($addresses->isEmpty())
            <div class="empty-state">
                <p class="mb-3">You have no saved addresses yet.</p>
                <a href="{{ route('account.addresses.create') }}" class="btn btn-brand">Add Your First Address</a>
            </div>
        @else
            <div class="row g-4">
                @foreach($addresses as $address)
                    <div class="col-md-6">
                        <article class="account-card address-card">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                <h2>{{ $address->label ?: 'Address' }}</h2>
                                <div class="d-flex flex-wrap gap-1 justify-content-end">
                                    @if($address->is_default_shipping)
                                        <span class="badge bg-success">Default Shipping</span>
                                    @endif
                                    @if($address->is_default_billing)
                                        <span class="badge bg-primary">Default Billing</span>
                                    @endif
                                </div>
                            </div>

                            <address class="mb-3">
                                <strong>{{ $address->recipient_name }}</strong>
                                @if($address->company)<br>{{ $address->company }}@endif
                                <br>{{ $address->address_line_1 }}
                                @if($address->address_line_2)<br>{{ $address->address_line_2 }}@endif
                                <br>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}
                                <br>{{ $address->country }}
                                @if($address->phone)<br>{{ $address->phone }}@endif
                            </address>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('account.addresses.edit', $address) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                                <form action="{{ route('account.addresses.destroy', $address) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this address?')">Delete</button>
                                </form>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
