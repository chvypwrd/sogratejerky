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
    $payment = $order->payments->first();
    $refundable = $payment?->refundable_cents ?? 0;
    $pickup = $order->pickup_location_snapshot;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="text-muted small">Order</div>
        <h1 class="h3 mb-1">{{ $order->order_number }}</h1>
        <div class="text-muted">Placed {{ $order->placed_at?->format('F j, Y g:i A') ?? 'â€”' }}</div>
    </div>
    <div class="text-end">
        <div class="mb-2">
            <a href="{{ route('admin.orders.work-order', $order) }}" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">Print Work Order</a>
            <a href="{{ route('admin.orders.invoice', $order) }}" class="btn btn-sm btn-outline-dark" target="_blank" rel="noopener">Print Invoice / Receipt</a>
        </div>
        <span class="badge bg-{{ $order->order_status->color() }}">{{ $order->order_status->label() }}</span>
        <span class="badge bg-{{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span>
        <span class="badge bg-{{ $order->fulfillment_status->color() }}">{{ $order->fulfillment_status->label() }}</span>
        @if($order->is_test_order)
            <span class="badge bg-warning text-dark">Test Order</span>
        @endif
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

@can('fulfill', $order)
    <div class="card mb-4">
        <div class="card-header"><strong>Test Order Classification</strong></div>
        <div class="card-body">
            <p class="text-muted small mb-3">Marking as a test order requires verified Stripe test-mode PaymentIntent evidence. This does not refund, cancel, or delete any payment or order records.</p>
            <form method="POST" action="{{ route('admin.orders.test-status', $order) }}">
                @csrf
                <input type="hidden" name="is_test_order" value="{{ $order->is_test_order ? '0' : '1' }}">
                <button type="submit" class="btn btn-sm {{ $order->is_test_order ? 'btn-outline-secondary' : 'btn-outline-warning' }}">
                    {{ $order->is_test_order ? 'Remove Test Order Flag' : 'Mark as Test Order' }}
                </button>
            </form>


        </div>
    </div>
@endcan

@if($order->is_test_order && $deletionInspection)
    @can('refund', $order)
        <div class="card border-danger mb-4">
            <div class="card-header"><strong>{{ $deletionInspection['eligible'] ? 'Delete Order' : 'Deletion Unavailable' }}</strong></div>
            <div class="card-body">
                @if($deletionInspection['eligible'])
                    <div class="alert alert-danger" role="alert">
                        <strong>Permanent deletion.</strong>
                        This removes the order, items, payments, refunds, fulfillment history, and activity history from the local database. No Stripe request is made.
                    </div>
                    <p class="small text-muted">
                        {{ $deletionInspection['counts']['order_items'] }} items &middot;
                        {{ $deletionInspection['counts']['payments'] }} payments &middot;
                        {{ $deletionInspection['counts']['refunds'] }} refunds &middot;
                        {{ $deletionInspection['counts']['activities'] }} activities
                    </p>
                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Permanently delete order {{ $order->order_number }}?')">
                        @csrf
                        @method('DELETE')
                        <div class="mb-3">
                            <label for="confirm_order_number" class="form-label">Type <strong>{{ $order->order_number }}</strong> to confirm</label>
                            <input type="text" class="form-control @error('confirm_order_number') is-invalid @enderror" id="confirm_order_number" name="confirm_order_number" autocomplete="off" required>
                            @error('confirm_order_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-danger">Delete Order</button>
                    </form>
                @else
                    <div class="alert alert-warning mb-0" role="alert">
                        Deletion unavailable: {{ $deletionInspection['reason'] }}
                    </div>
                @endif
            </div>
        </div>
    @endcan
@endif

@if(! $order->is_test_order && $legacyDeletionInspection)
    @can('refund', $order)
        <div class="card border-danger mb-4">
            <div class="card-header"><strong>{{ $legacyDeletionInspection['eligible'] ? 'Legacy Test Order Deletion' : 'Legacy Deletion Unavailable' }}</strong></div>
            <div class="card-body">
                @if($legacyDeletionInspection['eligible'])
                    <div class="alert alert-danger" role="alert">
                        <strong>Permanent legacy cleanup.</strong>
                        This order is not classified as a test order. Deletion removes the local order, items, payments, refunds, fulfillment history, and activity history. No Stripe request is made.
                        @if($legacyDeletionInspection['requires_override'])
                            <br><strong>The application cannot independently verify this order's payment mode.</strong>
                            Confirm only if this specific order was independently identified as a historical test order.
                        @endif
                    </div>
                    <p class="small text-muted">
                        {{ $legacyDeletionInspection['counts']['order_items'] }} items &middot;
                        {{ $legacyDeletionInspection['counts']['payments'] }} payments &middot;
                        {{ $legacyDeletionInspection['counts']['refunds'] }} refunds &middot;
                        {{ $legacyDeletionInspection['counts']['activities'] }} activities
                    </p>
                    <form method="POST" action="{{ route('admin.orders.legacy-destroy', $order) }}" onsubmit="return confirm('Permanently delete legacy order {{ $order->order_number }}?')">
                        @csrf
                        @method('DELETE')
                        <div class="mb-3">
                            <label for="legacy_confirm_order_number" class="form-label">Type <strong>{{ $order->order_number }}</strong> to confirm</label>
                            <input type="text" class="form-control @error('confirm_order_number') is-invalid @enderror" id="legacy_confirm_order_number" name="confirm_order_number" autocomplete="off" required>
                            @error('confirm_order_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @if($legacyDeletionInspection['requires_override'])
                            <div class="form-check mb-3">
                                <input class="form-check-input @error('legacy_override') is-invalid @enderror" type="checkbox" name="legacy_override" id="legacy_override" value="1">
                                <label class="form-check-label" for="legacy_override">I independently identified this specific order as a historical test order.</label>
                                @error('legacy_override')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="override_reason" class="form-label">Override reason</label>
                                <textarea class="form-control @error('override_reason') is-invalid @enderror" id="override_reason" name="override_reason" rows="2" required></textarea>
                                @error('override_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif
                        <button type="submit" class="btn btn-danger">Delete Legacy Test Order</button>
                    </form>
                @else
                    <div class="alert alert-warning mb-0" role="alert">
                        Legacy deletion unavailable: {{ $legacyDeletionInspection['reason'] }}
                    </div>
                @endif
            </div>
        </div>
    @endcan
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
                        <div>{{ $order->customer_phone ?: 'â€”' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Customer account</div>
                        <div>
                            @if($order->user)
                                Account #{{ $order->user->id }} â€” {{ $order->user->name }}
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
                        <td class="text-end">{{ $item->weight_grams ? $item->weight_grams.' g' : 'â€”' }}</td>
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

@if($payment)
    <div class="card mb-4">
        <div class="card-header"><strong>Payment & Refunds</strong></div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="text-muted small">Status</div>
                    <span class="badge bg-{{ $payment->status->color() }}">{{ $payment->status->label() }}</span>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Captured</div>
                    <div>{{ $payment->amount_formatted }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Refunded</div>
                    <div>${{ number_format($payment->refunded_cents / 100, 2) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Remaining refundable</div>
                    <div>${{ number_format($refundable / 100, 2) }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">Stripe PaymentIntent</div>
                    <div class="text-break">{{ $payment->payment_intent_id ?: 'â€”' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">Paid at</div>
                    <div>{{ $payment->paid_at?->format('M j, Y g:i A') ?? 'â€”' }}</div>
                </div>
            </div>

            @if($payment->refunds->isNotEmpty())
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th>Requested by</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payment->refunds as $refund)
                                <tr>
                                    <td class="text-nowrap">{{ $refund->created_at->format('M j, Y g:i A') }}</td>
                                    <td>{{ $refund->amount_formatted }}</td>
                                    <td><span class="badge bg-{{ $refund->status->color() }}">{{ $refund->status->label() }}</span></td>
                                    <td>{{ $refund->reason ?: 'â€”' }}</td>
                                    <td>{{ $refund->requester?->name ?? 'â€”' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @can('refund', $order)
                @if($refundable > 0 && in_array($order->payment_status, [\App\Enums\PaymentStatus::Paid, \App\Enums\PaymentStatus::PartiallyRefunded], true))
                    <form method="POST" action="{{ route('admin.orders.refund', $order) }}" class="row g-2 align-items-end">
                        @csrf
                        <input type="hidden" name="request_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                        <div class="col-md-3">
                            <label for="refund_amount" class="form-label">Refund amount</label>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" id="refund_amount" name="amount" min="0.01" max="{{ number_format($refundable / 100, 2, '.', '') }}" step="0.01" value="{{ old('amount', number_format($refundable / 100, 2, '.', '')) }}" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label for="refund_reason" class="form-label">Reason <span class="text-muted">(optional)</span></label>
                            <input type="text" class="form-control" id="refund_reason" name="reason" value="{{ old('reason') }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Submit this refund request to Stripe?')">Request Refund</button>
                        </div>
                    </form>
                @elseif($refundable > 0)
                    <p class="text-muted mb-0">Refunds are available after a captured payment is verified.</p>
                @else
                    <p class="text-muted mb-0">No refundable balance remains.</p>
                @endif
            @endcan
        </div>
    </div>
@endif

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
                        <td>{{ $activity->previous_status ? \Illuminate\Support\Str::headline($activity->previous_status) : 'â€”' }}</td>
                        <td>{{ $activity->new_status ? \Illuminate\Support\Str::headline($activity->new_status) : 'â€”' }}</td>
                        <td>{{ $activity->actor_name ?: ($activity->actor?->name ?? 'System') }}</td>
                        <td>{{ $activity->note ?: 'â€”' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No activity recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('cancel', $order)
    @if(! $order->isCancelled() && ! $order->isFulfillmentComplete() && in_array($order->payment_status, [\App\Enums\PaymentStatus::Pending, \App\Enums\PaymentStatus::Failed, \App\Enums\PaymentStatus::Refunded], true))
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
    @elseif(! $order->isCancelled() && $order->payment_status === \App\Enums\PaymentStatus::Paid)
        <p class="text-muted small">Issue a full refund before cancelling a paid, unfulfilled order.</p>
    @elseif(! $order->isCancelled() && $order->payment_status === \App\Enums\PaymentStatus::PartiallyRefunded)
        <p class="text-muted small">Fully refund the payment before cancelling this order.</p>
    @endif
@endcan

<a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary mt-2">Back to Orders</a>
@endsection
