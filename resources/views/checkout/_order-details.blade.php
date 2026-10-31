@php
    $fulfillmentAddress = $order->fulfillment_address_snapshot;
    $billingAddress = $order->billing_address_snapshot;
    $pickup = $order->pickup_location_snapshot;
@endphp

<div class="order-detail">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="text-muted small">Order number</div>
            <div class="h3 mb-1">{{ $order->order_number }}</div>
            <div class="text-muted">{{ $order->placed_at?->format('F j, Y g:i A') }}</div>
        </div>
        <div class="text-end">
            <span class="badge bg-{{ $order->order_status->color() }}">{{ $order->order_status->label() }}</span>
            <span class="badge bg-{{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span>
            <span class="badge bg-{{ $order->fulfillment_status->color() }}">{{ $order->fulfillment_status->label() }}</span>
        </div>
    </div>

    <div class="table-responsive mb-4">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>SKU</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit</th>
                    <th class="text-end">Line total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->product_name }}</strong>
                            <div class="text-muted small">{{ $item->variant_name }}</div>
                        </td>
                        <td>{{ $item->sku }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ $item->unit_price_formatted }}</td>
                        <td class="text-end">{{ $item->line_total_formatted }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <h2 class="h5">Fulfillment</h2>
            <p class="mb-1"><strong>{{ $order->fulfillment_method->label() }}</strong></p>
            @if($pickup)
                <address class="mb-2">
                    {{ $pickup['name'] ?? '' }}<br>
                    {!! nl2br(e($pickup['address'] ?? '')) !!}
                    @if(! empty($pickup['contact_details']))<br>{{ $pickup['contact_details'] }}@endif
                </address>
                @if(! empty($pickup['instructions']))
                    <p class="text-muted small">{{ $pickup['instructions'] }}</p>
                @endif
            @elseif($fulfillmentAddress)
                <address class="mb-2">
                    {{ $fulfillmentAddress['recipient_name'] ?? '' }}<br>
                    @if(! empty($fulfillmentAddress['company'])){{ $fulfillmentAddress['company'] }}<br>@endif
                    {{ $fulfillmentAddress['address_line_1'] ?? '' }}<br>
                    @if(! empty($fulfillmentAddress['address_line_2'])){{ $fulfillmentAddress['address_line_2'] }}<br>@endif
                    {{ $fulfillmentAddress['city'] ?? '' }}, {{ $fulfillmentAddress['state'] ?? '' }} {{ $fulfillmentAddress['postal_code'] ?? '' }}<br>
                    {{ $fulfillmentAddress['country'] ?? '' }}
                </address>
            @endif

            @if($order->shipping_carrier || $order->tracking_number)
                <div class="mt-3">
                    <div class="text-muted small">Shipping</div>
                    @if($order->shipping_carrier)
                        <div>Carrier: {{ $order->shipping_carrier->label() }}</div>
                    @endif
                    @if($order->tracking_number)
                        <div>
                            Tracking:
                            @if($order->tracking_url)
                                <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer">{{ $order->tracking_number }}</a>
                            @else
                                {{ $order->tracking_number }}
                            @endif
                        </div>
                    @else
                        <div>Tracking information is not available.</div>
                    @endif
                </div>
            @endif

            @if($order->fulfillment_status === \App\Enums\FulfillmentStatus::ReadyForPickup)
                <div class="alert alert-info mt-3 mb-0" role="status">Your order is ready for pickup.</div>
            @elseif($order->fulfillment_status === \App\Enums\FulfillmentStatus::OutForDelivery)
                <div class="alert alert-info mt-3 mb-0" role="status">Your order is out for delivery.</div>
            @endif

            @if($order->cancelled_at)
                <div class="alert alert-secondary mt-3 mb-0" role="status">
                    This order was cancelled on {{ $order->cancelled_at->format('F j, Y g:i A') }}.
                    @if($order->cancellation_reason)
                        <br>Reason: {{ $order->cancellation_reason }}
                    @endif
                </div>
            @endif

            @php
                $progress = collect([
                    'Placed' => $order->placed_at,
                    'Ready for pickup' => $order->ready_for_pickup_at,
                    'Picked up' => $order->picked_up_at,
                    'Out for delivery' => $order->out_for_delivery_at,
                    'Delivered' => $order->delivered_at,
                    'Shipped' => $order->shipped_at,
                    'Fulfilled' => $order->fulfilled_at,
                ])->filter();
            @endphp

            @if($progress->isNotEmpty())
                <div class="mt-3">
                    <div class="text-muted small">Progress</div>
                    <ul class="list-unstyled mb-0">
                        @foreach($progress as $label => $timestamp)
                            <li class="d-flex justify-content-between gap-3 py-1">
                                <span>{{ $label }}</span>
                                <span class="text-muted small">{{ $timestamp->format('M j, Y g:i A') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <h2 class="h5">Billing address</h2>
            @if($billingAddress)
                <address class="mb-0">
                    {{ $billingAddress['recipient_name'] ?? '' }}<br>
                    @if(! empty($billingAddress['company'])){{ $billingAddress['company'] }}<br>@endif
                    {{ $billingAddress['address_line_1'] ?? '' }}<br>
                    @if(! empty($billingAddress['address_line_2'])){{ $billingAddress['address_line_2'] }}<br>@endif
                    {{ $billingAddress['city'] ?? '' }}, {{ $billingAddress['state'] ?? '' }} {{ $billingAddress['postal_code'] ?? '' }}<br>
                    {{ $billingAddress['country'] ?? '' }}
                </address>
            @else
                <p class="text-muted mb-0">No billing address recorded.</p>
            @endif
        </div>

        <div class="col-md-4">
            <h2 class="h5">Totals</h2>
            <div class="checkout-summary__row"><span>Subtotal</span><strong>${{ number_format($order->subtotal_cents / 100, 2) }}</strong></div>
            <div class="checkout-summary__row"><span>Fulfillment</span><strong>${{ number_format($order->fulfillment_fee_cents / 100, 2) }}</strong></div>
            <div class="checkout-summary__row"><span>Tax</span><strong>${{ number_format($order->tax_cents / 100, 2) }}</strong></div>
            <div class="checkout-summary__row checkout-summary__total"><span>Total</span><strong>{{ $order->total_formatted }}</strong></div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-md-6">
            <h2 class="h5">Contact</h2>
            <p class="mb-1">{{ $order->customer_name }}</p>
            <p class="mb-1">{{ $order->customer_email }}</p>
            @if($order->customer_phone)<p class="mb-0">{{ $order->customer_phone }}</p>@endif
        </div>
    </div>
</div>
