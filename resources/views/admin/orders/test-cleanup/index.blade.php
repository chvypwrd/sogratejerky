@extends('admin.layouts.app')

@section('title', 'Test Order Cleanup')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Test Order Cleanup</h1>
        <p class="text-muted mb-0">Dry-run review of test orders. Deletion is permanent and is only available for verified test-mode orders.</p>
    </div>
    <a href="{{ route('admin.orders.index', ['order_type' => 'test']) }}" class="btn btn-outline-secondary">View Test Orders</a>
</div>

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

<div class="alert alert-warning" role="alert">
    This page does not delete anything automatically. Review the affected record counts and open an order's deletion preview before confirming.
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Placed</th>
                    <th>Total</th>
                    <th>Records</th>
                    <th>Verification</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $inspection = $order->test_cleanup;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="fw-medium">{{ $order->order_number }}</a>
                            @if($inspection['eligible'])
                                <span class="badge bg-success ms-1">Eligible</span>
                            @else
                                <span class="badge bg-secondary ms-1">Ineligible</span>
                            @endif
                        </td>
                        <td>{{ $order->placed_at?->format('M j, Y') ?? $order->created_at->format('M j, Y') }}</td>
                        <td>{{ $order->total_formatted }}</td>
                        <td class="small">
                            {{ $inspection['counts']['order_items'] }} items ·
                            {{ $inspection['counts']['payments'] }} payments ·
                            {{ $inspection['counts']['refunds'] }} refunds ·
                            {{ $inspection['counts']['activities'] }} activities
                        </td>
                        <td class="small">
                            @if($inspection['eligible'])
                                {{ $inspection['verification_basis'] }}
                            @else
                                <span class="text-muted">{{ $inspection['reason'] }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.test-cleanup.preview', $order) }}" class="btn btn-sm btn-outline-danger">Review Deletion</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No test orders are currently classified.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection
