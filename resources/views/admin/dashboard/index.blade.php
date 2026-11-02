@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">Welcome, {{ Auth::user()->name }}</p>
    </div>
    <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex align-items-end gap-2">
        <div>
            <label for="month" class="form-label small text-muted mb-1">Financial month</label>
            <input type="month" class="form-control form-control-sm" id="month" name="month" value="{{ $report['month'] }}">
        </div>
        <button type="submit" class="btn btn-sm btn-outline-secondary">Apply</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Orders today</div>
            <div class="h3 mb-0">{{ number_format($report['ordersToday']) }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Orders · {{ $report['monthLabel'] }}</div>
            <div class="h3 mb-0">{{ number_format($report['ordersMonth']) }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Gross sales today</div>
            <div class="h3 mb-0">{{ $report['grossTodayFormatted'] }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Net sales · {{ $report['monthLabel'] }}</div>
            <div class="h3 mb-0">{{ $report['netMonthFormatted'] }}</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Awaiting fulfillment</div>
            <div class="h3 mb-0">{{ number_format($report['awaitingFulfillment']) }}</div>
            <a href="{{ route('admin.orders.index', ['fulfillment_status' => 'unfulfilled']) }}" class="small">Review orders</a>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-4 col-xxl-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Unread messages</div>
            <div class="h3 mb-0">{{ number_format($report['unreadMessages']) }}</div>
            @can('contacts.view')
                <a href="{{ route('admin.contact-messages.index', ['filter' => 'new']) }}" class="small">Open inbox</a>
            @endcan
        </div></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Sales trend · last 30 days</strong>
                <span class="small text-muted">Gross, refunds, net</span>
            </div>
            <div class="card-body">
                <canvas id="sales-trend-chart" height="110" aria-label="Sales trend for the last 30 days" role="img"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><strong>Financial summary · {{ $report['monthLabel'] }}</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom"><span>Gross sales</span><strong>{{ $report['grossMonthFormatted'] }}</strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span>Refunds</span><strong>{{ $report['refundsMonthFormatted'] }}</strong></div>
                <div class="d-flex justify-content-between py-2"><span>Net sales</span><strong>{{ $report['netMonthFormatted'] }}</strong></div>
                <p class="small text-muted mb-0 mt-2">Collected revenue is based on successful payments. Refund totals include succeeded refunds only.</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Orders by fulfillment method</strong></div>
            <div class="card-body">
                <canvas id="fulfillment-chart" height="150" aria-label="Orders by fulfillment method for {{ $report['monthLabel'] }}" role="img"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Best-selling products · {{ $report['monthLabel'] }}</strong></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Product</th><th class="text-center">Units</th><th class="text-end">Gross</th></tr></thead>
                    <tbody>
                        @forelse($report['bestSellers'] as $item)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $item->product_name }}</div>
                                    <div class="small text-muted">{{ $item->variant_name }}</div>
                                </td>
                                <td class="text-center">{{ number_format($item->units) }}</td>
                                <td class="text-end">${{ number_format($item->revenue_cents / 100, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No paid product sales in this month.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Recent orders</strong>
                @can('orders.view')
                    <a href="{{ route('admin.orders.index') }}" class="small">View all orders</a>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse($report['recentOrders'] as $order)
                            <tr>
                                <td>
                                    @can('orders.view')
                                        <a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a>
                                    @else
                                        {{ $order->order_number }}
                                    @endcan
                                    <div class="small text-muted">{{ $order->placed_at?->format('M j, Y g:i A') }}</div>
                                </td>
                                <td>{{ $order->customer_name }}</td>
                                <td>
                                    <span class="badge bg-{{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span>
                                    <span class="badge bg-{{ $order->fulfillment_status->color() }}">{{ $order->fulfillment_status->label() }}</span>
                                </td>
                                <td class="text-end">{{ $order->total_formatted }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><strong>Google Analytics</strong></div>
            <div class="card-body">
                <p class="text-muted small">Visitor and acquisition reporting is available in Google Analytics. This dashboard does not claim to display GA4 reports without a Data API connection.</p>
                <a href="https://analytics.google.com/" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Open Google Analytics</a>
                @if($ga4Configured)
                    <span class="badge bg-success ms-2">Configured</span>
                @else
                    <span class="badge bg-secondary ms-2">Not configured</span>
                @endif
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header"><strong>Inventory</strong></div>
            <div class="card-body">
                <p class="text-muted small mb-0">Inventory tracking is not enabled in this application. Product availability is controlled by active products and variants.</p>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><strong>Account security</strong></div>
            <div class="card-body">
                <p class="text-muted small mb-2">Manage two-factor authentication and account security settings.</p>
                <a href="{{ route('admin.security') }}" class="small">Open security settings</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const salesCanvas = document.getElementById('sales-trend-chart');
    if (salesCanvas && window.Chart) {
        new Chart(salesCanvas, {
            type: 'line',
            data: {
                labels: @json($report['salesTrendLabels']),
                datasets: [
                    { label: 'Gross', data: @json($report['salesTrendGross']), borderColor: '#9B300F', backgroundColor: 'rgba(155,48,15,0.08)', tension: 0.3, fill: true },
                    { label: 'Refunds', data: @json($report['salesTrendRefunds']), borderColor: '#C75B3A', backgroundColor: 'rgba(199,91,58,0.05)', tension: 0.3 },
                    { label: 'Net', data: @json($report['salesTrendNet']), borderColor: '#2B2B2B', borderDash: [5, 4], tension: 0.3 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: { y: { beginAtZero: true, ticks: { callback: (value) => '$' + (value / 100).toFixed(0) } } },
                plugins: { tooltip: { callbacks: { label: (context) => context.dataset.label + ': $' + (context.parsed.y / 100).toFixed(2) } } },
            },
        });
    }

    const fulfillmentCanvas = document.getElementById('fulfillment-chart');
    if (fulfillmentCanvas && window.Chart) {
        new Chart(fulfillmentCanvas, {
            type: 'doughnut',
            data: {
                labels: @json($report['fulfillmentMethods']->pluck('label')),
                datasets: [{ data: @json($report['fulfillmentMethods']->pluck('value')), backgroundColor: ['#9B300F', '#C75B3A', '#D4956A'] }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
    }
});
</script>
@endpush
