@extends('admin.layouts.app')

@section('title', 'Delete Test Order '.$order->order_number)

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Delete Test Order</h1>
    <p class="text-muted mb-0">Permanent local database cleanup for order <strong>{{ $order->order_number }}</strong>.</p>
</div>

<div class="alert alert-danger" role="alert">
    <strong>This action is irreversible.</strong>
    The order, order items, payments, refunds, and activity history will be permanently deleted from the local database. Stripe will not be refunded or cancelled.
</div>

@if($inspection['eligible'])
    <div class="alert alert-success" role="status">
        Verified test-mode order. Verification basis: <strong>{{ $inspection['verification_basis'] }}</strong>.
    </div>
@else
    <div class="alert alert-warning" role="alert">
        This order is <strong>not eligible</strong> for deletion: {{ $inspection['reason'] }}
    </div>
@endif

<div class="card mb-4">
    <div class="card-header"><strong>Affected Records</strong></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3"><div class="text-muted small">Order items</div><div class="h4 mb-0">{{ $inspection['counts']['order_items'] }}</div></div>
            <div class="col-sm-6 col-lg-3"><div class="text-muted small">Payments</div><div class="h4 mb-0">{{ $inspection['counts']['payments'] }}</div></div>
            <div class="col-sm-6 col-lg-3"><div class="text-muted small">Refunds</div><div class="h4 mb-0">{{ $inspection['counts']['refunds'] }}</div></div>
            <div class="col-sm-6 col-lg-3"><div class="text-muted small">Activities</div><div class="h4 mb-0">{{ $inspection['counts']['activities'] }}</div></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Order Summary</strong></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><div class="text-muted small">Order number</div><div>{{ $order->order_number }}</div></div>
            <div class="col-md-4"><div class="text-muted small">Customer</div><div>{{ $order->customer_name }}</div></div>
            <div class="col-md-4"><div class="text-muted small">Total</div><div>{{ $order->total_formatted }}</div></div>
            <div class="col-md-4"><div class="text-muted small">Placed</div><div>{{ $order->placed_at?->format('M j, Y g:i A') ?? $order->created_at->format('M j, Y g:i A') }}</div></div>
            <div class="col-md-4"><div class="text-muted small">Payment</div><div>{{ $order->payment_status->label() }}</div></div>
            <div class="col-md-4"><div class="text-muted small">Fulfillment</div><div>{{ $order->fulfillment_status->label() }}</div></div>
        </div>
    </div>
</div>

@if($inspection['eligible'])
    <div class="card border-danger">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.orders.test-cleanup.destroy', $order) }}" onsubmit="return confirm('Permanently delete test order {{ $order->order_number }}?')">
                @csrf
                @method('DELETE')
                <div class="mb-3">
                    <label for="confirm_order_number" class="form-label">Type <strong>{{ $order->order_number }}</strong> to confirm</label>
                    <input type="text" class="form-control @error('confirm_order_number') is-invalid @enderror" id="confirm_order_number" name="confirm_order_number" autocomplete="off" required>
                    @error('confirm_order_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-danger">Permanently Delete Test Order</button>
                <a href="{{ route('admin.orders.test-cleanup') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>
@else
    <a href="{{ route('admin.orders.test-cleanup') }}" class="btn btn-outline-secondary">Back to Cleanup</a>
@endif
@endsection
