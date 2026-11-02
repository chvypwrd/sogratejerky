@php
    $pickup = $order->pickup_location_snapshot;
    $address = $order->fulfillment_address_snapshot;
    $paymentStatus = $order->payment_status;
    $pickupInstructions = $pickup['instructions'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Work Order {{ $order->order_number }}</title>
    @include('admin.orders.print._styles')
</head>
<body>
    <div class="print-page">
        <div class="print-actions no-print">
            <button type="button" onclick="window.print()">Print Work Order</button>
            <a href="{{ route('admin.orders.show', $order) }}">Back to Order</a>
        </div>

        <header class="print-header">
            <div>
                <img src="{{ $logoUrl }}" alt="{{ $business['name'] }}">
                <div class="muted">{{ $business['name'] }}</div>
            </div>
            <div class="print-meta">
                <h1>Work Order</h1>
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
                <div class="print-label">Fulfillment</div>
                <div class="print-value">{{ $order->fulfillment_method->label() }}</div>
                @if($pickup)
                    <address>
                        {{ $pickup['name'] ?? 'Pickup location' }}<br>
                        {!! nl2br(e($pickup['address'] ?? '')) !!}
                        @if(! empty($pickup['contact_details']))<br>{{ $pickup['contact_details'] }}@endif
                    </address>
                @elseif($address)
                    <address>
                        {{ $address['recipient_name'] ?? '' }}<br>
                        @if(! empty($address['company'])){{ $address['company'] }}<br>@endif
                        {{ $address['address_line_1'] ?? '' }}<br>
                        @if(! empty($address['address_line_2'])){{ $address['address_line_2'] }}<br>@endif
                        {{ $address['city'] ?? '' }}, {{ $address['state'] ?? '' }} {{ $address['postal_code'] ?? '' }}<br>
                        {{ $address['country'] ?? '' }}
                    </address>
                @endif
            </div>
            <div>
                <div class="print-label">Payment status</div>
                <span class="status">{{ $paymentStatus->label() }}</span>
            </div>
            <div>
                <div class="print-label">Order total</div>
                <div class="print-value">{{ $order->total_formatted }}</div>
            </div>
        </div>

        <h2>Items to Pack</h2>
        <table>
            <thead>
                <tr>
                    <th class="center" style="width: 28px;">Packed</th>
                    <th>Product</th>
                    <th>Variant</th>
                    <th>SKU</th>
                    <th class="num">Qty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $item)
                    <tr>
                        <td class="center"><span class="check-box"></span></td>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->variant_name }}</td>
                        <td>{{ $item->sku ?: '-' }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="center muted">No item records are available for this order.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="print-grid">
            <div>
                <div class="print-label">Order notes</div>
                <div class="note-box">{{ $orderNote ?: 'None recorded.' }}</div>
            </div>
            <div>
                <div class="print-label">Fulfillment instructions</div>
                <div class="note-box">{{ $pickupInstructions ?: 'None recorded.' }}</div>
            </div>
        </div>

        <div class="signature-grid">
            <div class="signature-line">Packed by</div>
            <div class="signature-line">Date</div>
        </div>

        <div class="print-footer">
            Internal fulfillment document. Printing this work order does not change order or payment status.
        </div>
    </div>
</body>
</html>
