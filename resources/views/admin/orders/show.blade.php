@extends('admin.layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
@php
    $isPickup = $order->fulfillment_method === \App\Enums\FulfillmentMethod::Pickup;
    $isLocalDelivery = $order->fulfillment_method === \App\Enums\FulfillmentMethod::LocalDelivery;
    $isShipping = $order->fulfillment_method === \App\Enums\FulfillmentMethod::Shipping;
    $isUnfulfilled = $order->fulfillment_status === \App\Enums\FulfillmentStatus::Unfulfilled;
    $isReady = $order->fulfillment_status === \App\Enums\FulfillmentStatus::ReadyForPickup;
    $isOutForDelivery = $order->fulfillment_status === \App\Enums\FulfillmentStatus::OutForDelivery;
    $canProgress = $order->isPaid() && ! $order->isCancelled() && ! $order->isFulfillmentComplete();
    $timeline = collect([
        'Placed' => $order->placed_at,
        'Ready for pickup' => $order->ready_for_pickup_at,
        'Picked up' => $order->picked_up_at,
        'Out for delivery' => $order->out_for_delivery_at,
        'Delivered' => $order->delivered_at,
        'Shipped' => $order->shipped_at,
        'Fulfilled' => $order->fulfilled_at,
        'Cancelled' => $order->cancelled_at,
    ])->filter();
    $fulfillmentAddress = $order->fulfillment_address_snapshot;
    $billingAddress = $order->billing_address_snapshot;
    $pickup = $order->pickup_location_snapshot;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-muted small">Order</div>
        <h1 class="h3 mb-1">{{ $order->order_number }}</h1>
        <div class="text-muted">Placed {{ $order->placed_at?->format('F j, Y g:i A') ?? '—' }}</div>
    </div>
    <div class="text-end">
        <span class="badge bg-{{ $order->order_status->color() }}">{{ $order->order_status->label() }}</span>
        <span class="badge bg-{{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span>
        <span class="badge bg-{{ $order->fulfillment_status->color() }}">{{ $order->fulfillment_status->label() }}</span>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success" role="status">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Action could not be completed.</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><strong>Customer</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Name</div>
                        <div>{{ $order->customer_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Email</div>
                        <div>{{ $order->customer_email }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Phone</div>
                        <div>{{ $order->customer_phone ?: '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Customer account</div>
                        <div>
                            @if($order->user)
                                Account #{{ $order->user->id }} — {{ $order->user->name }}
                            @else
                                <span class="badge bg-secondary">Guest</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><strong>Financial Summary</strong></div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1"><span>Merchandise</span><strong>${{ number_format($order->subtotal_cents / 100, 2) }}</strong></div>
                <div class="d-flex justify-content-between py-1"><span>Fulfillment</span><strong>${{ number_format($order->fulfillment_fee_cents / 100, 2) }}</strong></div>
                <div class="d-flex justify-content-between py-1"><span>Tax</span><strong>${{ number_format($order->tax_cents / 100, 2) }}</strong></div>
                <div class="d-flex justify-content-between border-top mt-2 pt-2"><span>Total</span><strong>{{ $order->total_formatted }}</strong></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Order Items</strong></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Variant</th>
                    <th>SKU</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit</th>
                    <th class="text-end">Line total</th>
                    <th class="text-end">Weight</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->variant_name }}</td>
                        <td>{{ $item->sku }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ $item->unit_price_formatted }}</td>
                        <td class="text-end">{{ $item->line_total_formatted }}</td>
                        <td class="text-end">{{ $item->weight_grams ? $item->weight_grams.' g' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Fulfillment</strong></div>
            <div class="card-body">
                <p class="mb-2"><strong>{{ $order->fulfillment_method->label() }}</strong></p>

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

                @if($isShipping)
                    <hr>
                    <div class="text-muted small">Shipping details</div>
                    @if($order->shipping_carrier)
                        <div>Carrier: {{ $order->shipping_carrier->label() }}</div>
                    @else
                        <div>Carrier: Not recorded</div>
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
                        <div>Tracking: Not available</div>
                    @endif
                @endif

                @if($order->cancellation_reason)
                    <hr>
                    <div class="text-muted small">Cancellation reason</div>
                    <div>{{ $order->cancellation_reason }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Billing Address</strong></div>
            <div class="card-body">
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
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Fulfillment Actions</strong></div>
            <div class="card-body">
                @if($order->isCancelled())
                    <p class="text-muted mb-0">Cancelled orders cannot be fulfilled.</p>
                @elseif(! $order->isPaid())
                    <p class="text-muted mb-0">Payment must be verified as paid before fulfillment. Payment confirmation is added in Milestone 10.</p>
                @elseif($order->isFulfillmentComplete())
                    <p class="text-muted mb-0">This order is fulfilled.</p>
                @else
                    @can('fulfill', $order)
                        @if($isPickup && $isUnfulfilled)
                            <form method="POST" action="{{ route('admin.orders.ready-for-pickup', $order) }}" class="mb-3">
                                @csrf
                                <label for="ready-note" class="form-label">Operational note <span class="text-muted">(optional)</span></label>
                                <textarea class="form-control mb-2" id="ready-note" name="note" rows="2"></textarea>
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Mark this order ready for pickup?')">Mark Ready for Pickup</button>
                            </form>
                        @elseif($isPickup && $isReady)
                            <form method="POST" action="{{ route('admin.orders.picked-up', $order) }}">
                                @csrf
                                <label for="picked-note" class="form-label">Operational note <span class="text-muted">(optional)</span></label>
                                <textarea class="form-control mb-2" id="picked-note" name="note" rows="2"></textarea>
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm this order was picked up?')">Mark Picked Up</button>
                            </form>
                        @elseif($isLocalDelivery && $isUnfulfilled)
                            <form method="POST" action="{{ route('admin.orders.out-for-delivery', $order) }}" class="mb-3">
                                @csrf
                                <label for="out-note" class="form-label">Operational note <span class="text-muted">(optional)</span></label>
                                <textarea class="form-control mb-2" id="out-note" name="note" rows="2"></textarea>
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Mark this order out for delivery?')">Mark Out for Delivery</button>
                            </form>
                        @elseif($isLocalDelivery && $isOutForDelivery)
                            <form method="POST" action="{{ route('admin.orders.delivered', $order) }}">
                                @csrf
                                <label for="delivered-note" class="form-label">Operational note <span class="text-muted">(optional)</span></label>
                                <textarea class="form-control mb-2" id="delivered-note" name="note" rows="2"></textarea>
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm this order was delivered?')">Mark Delivered</button>
                            </form>
                        @elseif($isShipping && $isUnfulfilled)
                            <form method="POST" action="{{ route('admin.orders.shipped', $order) }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="shipping_carrier" class="form-label">Carrier</label>
                                    <select class="form-select" id="shipping_carrier" name="shipping_carrier" required>
                                        <option value="">Select carrier</option>
                                        @foreach($carriers as $carrier)
                                            <option value="{{ $carrier->value }}" @selected(old('shipping_carrier') === $carrier->value)>{{ $carrier->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="tracking_number" class="form-label">Tracking number</label>
                                    <input type="text" class="form-control" id="tracking_number" name="tracking_number" value="{{ old('tracking_number') }}">
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="untracked" id="untracked" value="1" @checked(old('untracked'))>
                                    <label class="form-check-label" for="untracked">Untracked shipment (no tracking number available)</label>
                                </div>
                                <label for="shipping-note" class="form-label">Shipping note <span class="text-muted">(optional)</span></label>
                                <textarea class="form-control mb-2" id="shipping-note" name="note" rows="2"></textarea>
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Mark this order shipped?')">Mark Shipped</button>
                            </form>
                        @else
                            <p class="text-muted mb-0">No fulfillment action is available for the current status.</p>
                        @endif
                    @else
                        <p class="text-muted mb-0">You do not have permission to fulfill orders.</p>
                    @endcan
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>Status Timeline</strong></div>
            <div class="card-body">
                @if($timeline->isEmpty())
                    <p class="text-muted mb-0">No status timestamps recorded.</p>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach($timeline as $label => $timestamp)
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span>{{ $label }}</span>
                                <span class="text-muted">{{ $timestamp->format('M j, Y g:i A') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Order Activity</strong></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Event</th>
                    <th>Previous</th>
                    <th>New</th>
                    <th>Actor</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->activities as $activity)
                    <tr>
                        <td class="text-nowrap">{{ $activity->occurred_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $activity->event->label() }}</td>
                        <td>{{ $activity->previous_status ? \Illuminate\Support\Str::headline($activity->previous_status) : '—' }}</td>
                        <td>{{ $activity->new_status ? \Illuminate\Support\Str::headline($activity->new_status) : '—' }}</td>
                        <td>{{ $activity->actor_name ?: ($activity->actor?->name ?? 'System') }}</td>
                        <td>{{ $activity->note ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No activity recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('cancel', $order)
    @if(! $order->isCancelled() && ! $order->isPaid() && ! $order->isFulfillmentComplete())
        <div class="card border-danger">
            <div class="card-header text-danger"><strong>Cancel Order</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}">
                    @csrf
                    <label for="cancellation_reason" class="form-label">Cancellation reason <span class="text-muted">(optional)</span></label>
                    <textarea class="form-control mb-2" id="cancellation_reason" name="reason" rows="2"></textarea>
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Cancel this order? This does not issue a refund.')">Cancel Order</button>
                </form>
            </div>
        </div>
    @elseif(! $order->isCancelled() && $order->isPaid())
        <p class="text-muted small">Paid orders cannot be cancelled until payment refunds are supported.</p>
    @endif
@endcan

<a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary mt-2">Back to Orders</a>
@endsection
