@extends('admin.layouts.app')

@section('title', 'Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Orders</h1>
        <p class="text-muted mb-0">Review payments, fulfillment progress, and customer orders.</p>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Awaiting payment</div>
            <div class="h3 mb-0">{{ $summary['awaiting_payment'] }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Paid, awaiting fulfillment</div>
            <div class="h3 mb-0">{{ $summary['paid_awaiting_fulfillment'] }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Ready for pickup</div>
            <div class="h3 mb-0">{{ $summary['ready_for_pickup'] }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Out for delivery</div>
            <div class="h3 mb-0">{{ $summary['out_for_delivery'] }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Fulfilled in last 7 days</div>
            <div class="h3 mb-0">{{ $summary['recently_fulfilled'] }}</div>
        </div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-3">
            <div class="col-lg-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Order number, name, or email">
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="order_status" class="form-label">Order status</label>
                <select class="form-select" id="order_status" name="order_status">
                    <option value="">All</option>
                    @foreach($orderStatuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['order_status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="payment_status" class="form-label">Payment</label>
                <select class="form-select" id="payment_status" name="payment_status">
                    <option value="">All</option>
                    @foreach($paymentStatuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['payment_status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="fulfillment_status" class="form-label">Fulfillment</label>
                <select class="form-select" id="fulfillment_status" name="fulfillment_status">
                    <option value="">All</option>
                    @foreach($fulfillmentStatuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['fulfillment_status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="fulfillment_method" class="form-label">Method</label>
                <select class="form-select" id="fulfillment_method" name="fulfillment_method">
                    <option value="">All</option>
                    @foreach($fulfillmentMethods as $method)
                        <option value="{{ $method->value }}" @selected(($filters['fulfillment_method'] ?? '') === $method->value)>{{ $method->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="sort" class="form-label">Sort</label>
                <select class="form-select" id="sort" name="sort">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest first</option>
                    <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest first</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="date_from" class="form-label">From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-4 col-lg-2">
                <label for="date_to" class="form-label">To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filter Orders</button>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Method</th>
                    <th>Order</th>
                    <th>Payment</th>
                    <th>Fulfillment</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="fw-medium text-decoration-none">{{ $order->order_number }}</a>
                        </td>
                        <td class="text-nowrap">{{ $order->placed_at?->format('M j, Y') ?? $order->created_at->format('M j, Y') }}</td>
                        <td>
                            {{ $order->customer_name }}
                            <div class="small text-muted">{{ $order->customer_email }}</div>
                        </td>
                        <td class="text-nowrap">{{ $order->total_formatted }}</td>
                        <td class="text-nowrap">{{ $order->fulfillment_method->label() }}</td>
                        <td><span class="badge bg-{{ $order->order_status->color() }}">{{ $order->order_status->label() }}</span></td>
                        <td><span class="badge bg-{{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span></td>
                        <td><span class="badge bg-{{ $order->fulfillment_status->color() }}">{{ $order->fulfillment_status->label() }}</span></td>
                        <td class="text-end"><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No orders match the current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection
