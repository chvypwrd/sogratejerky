@extends('layouts.app')

@section('title', 'Order History')

@section('meta')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Account</span>
            <h1>Order History</h1>
            <p class="lead text-muted">Review the orders placed with your account.</p>
        </header>

        @include('account._nav')

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if($orders->isEmpty())
            <div class="empty-state">
                <p class="mb-3">You have not placed any orders yet.</p>
                <a href="{{ route('shop.index') }}" class="btn btn-brand">Shop Jerky</a>
            </div>
        @else
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Placed</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td>
                                            <strong>{{ $order->order_number }}</strong>
                                            @php
                                                $summaryItems = $order->items->take(3);
                                                $moreItemCount = $order->items->count() - $summaryItems->count();
                                            @endphp
                                            <div class="small text-muted mt-1">
                                                @if($order->items->isEmpty())
                                                    <span>Item details unavailable</span>
                                                @else
                                                    @foreach($summaryItems as $item)
                                                        <span class="d-block">
                                                            {{ $item->product_name }}
                                                            @if($item->variant_name)
                                                                <span class="text-muted">- {{ $item->variant_name }}</span>
                                                            @endif
                                                            <span class="fw-semibold">&times; {{ $item->quantity }}</span>
                                                        </span>
                                                    @endforeach
                                                    @if($moreItemCount > 0)
                                                        <span class="d-block mt-1">+ {{ $moreItemCount }} more {{ \Illuminate\Support\Str::plural('item', $moreItemCount) }}</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $order->placed_at?->format('M j, Y') ?? '—' }}</td>
                                        <td>{{ $order->items_count }}</td>
                                        <td><span class="badge bg-{{ $order->order_status->color() }}">{{ $order->order_status->label() }}</span></td>
                                        <td>{{ $order->total_formatted }}</td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="{{ route('account.orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View Order</a>
                                                <form method="POST" action="{{ route('account.orders.reorder', $order) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-brand-outline">Reorder</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-4">{{ $orders->links() }}</div>
        @endif
    </div>
</section>
@endsection
