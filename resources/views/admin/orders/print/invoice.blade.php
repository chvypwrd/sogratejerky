@php
    $isReceipt = in_array($order->payment_status, [
        \App\Enums\PaymentStatus::Paid,
        \App\Enums\PaymentStatus::PartiallyRefunded,
        \App\Enums\PaymentStatus::Refunded,
    ], true);
    $documentTitle = match ($order->payment_status) {
        \App\Enums\PaymentStatus::Paid => 'Receipt',
        \App\Enums\PaymentStatus::PartiallyRefunded => 'Partial Refund Receipt',
        \App\Enums\PaymentStatus::Refunded => 'Refund Receipt',
        default => 'Order Summary',
    };
    $payment = $payment ?? $order->payments->first();
    $refunds = $payment
        ? $payment->refunds->filter(fn ($refund) => $refund->status === \App\Enums\RefundStatus::Succeeded)
        : collect();
    $amountPaid = $payment?->amount_cents ?? 0;
    $netPaid = max(0, $amountPaid - $refundedCents);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $documentTitle }} {{ $order->order_number }}</title>
    @include('admin.orders.print._styles')
</head>
<body>
    <div class="print-page">
        <div class="print-actions no-print">
            <button type="button" onclick="window.print()">Print {{ $documentTitle }}</button>
            <a href="{{ route('admin.orders.show', $order) }}">Back to Order</a>
        </div>

        <header class="print-header">
            <div>
                <img src="{{ $logoUrl }}" alt="{{ $business['name'] }}">
                <div class="print-value">{{ $business['name'] }}</div>
                @if($business['address'])<div class="muted">{!! nl2br(e($business['address'])) !!}</div>@endif
                @if($business['email'])<div class="muted">{{ $business['email'] }}</div>@endif
                @if($business['phone'])<div class="muted">{{ $business['phone'] }}</div>@endif
            </div>
            <div class="print-meta">
                <h1>{{ $documentTitle }}</h1>
                <div class="print-value">{{ $order->order_number }}</div>
                <div class="muted">{{ $order->placed_at?->format('F j, Y g:i A') ?? $order->created_at->format('F j, Y g:i A') }}</div>
            </div>
        </header>

        <div class="print-grid">
            <div>
                <div class="print-label">Customer</div>
                <div class="print-value">{{ $order->customer_name }}</div>
                <div>{{ $order->customer_email }}</div>
                @if($order->customer_phone)<div>{{ $order->customer_phone }}</div>@endif
            </div>
            <div>
                <div class="print-label">Payment status</div>
                <span class="status">{{ $order->payment_status->label() }}</span>
                @if($payment?->paid_at)
                    <div class="muted" style="margin-top: 4px;">Paid {{ $payment->paid_at->format('F j, Y') }}</div>
                @endif
            </div>
        </div>

        <h2>Purchased Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Variant</th>
                    <th>SKU</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit price</th>
                    <th class="num">Line total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->variant_name }}</td>
                        <td>{{ $item->sku ?: '-' }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ $item->unit_price_formatted }}</td>
                        <td class="num">{{ $item->line_total_formatted }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="center muted">No item records are available for this order.</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Merchandise</td><td class="num">${{ number_format($order->subtotal_cents / 100, 2) }}</td></tr>
            <tr><td>{{ $order->fulfillment_method->label() }} charge</td><td class="num">${{ number_format($order->fulfillment_fee_cents / 100, 2) }}</td></tr>
            <tr><td>Taxes</td><td class="num">${{ number_format($order->tax_cents / 100, 2) }}</td></tr>
            <tr><td>{{ $isReceipt ? 'Total charged' : 'Order total' }}</td><td class="num">{{ $order->total_formatted }}</td></tr>
        </table>

        @if($isReceipt)
            <div class="print-grid" style="margin-top: 18px;">
                <div>
                    <div class="print-label">Payment received</div>
                    <div class="print-value">${{ number_format($amountPaid / 100, 2) }}</div>
                </div>
                <div>
                    <div class="print-label">Refunded</div>
                    <div class="print-value">${{ number_format($refundedCents / 100, 2) }}</div>
                </div>
                <div>
                    <div class="print-label">Net paid</div>
                    <div class="print-value">${{ number_format($netPaid / 100, 2) }}</div>
                </div>
            </div>

            @if($refunds->isNotEmpty())
                <h2>Refund Information</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($refunds as $refund)
                            <tr>
                                <td>{{ $refund->refunded_at?->format('M j, Y') ?? $refund->created_at->format('M j, Y') }}</td>
                                <td>{{ $refund->amount_formatted }}</td>
                                <td>{{ $refund->status->label() }}</td>
                                <td>{{ $refund->reason ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @else
            <p class="muted" style="margin-top: 14px;">This document is an order summary. Payment has not been confirmed as paid.</p>
        @endif

        <div class="print-footer">
            Questions about this order? Contact {{ $business['name'] }}@if($business['email']) at {{ $business['email'] }}@endif.
        </div>
    </div>
</body>
</html>
