@extends('layouts.app')

@section('title', 'Checkout')

@section('meta')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
@php
    $defaultMethod = $setting->shipping_enabled
        ? 'shipping'
        : ($setting->local_delivery_enabled ? 'local_delivery' : 'pickup');
@endphp

<section class="py-5 checkout-page">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Checkout</span>
            <h1>Complete Your Order</h1>
            <p class="lead text-muted">Choose how you would like to receive your Sograte jerky.</p>
        </header>

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Please review the errors below.</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @auth
            <section class="checkout-card mb-4" aria-labelledby="checkout-account-heading">
                <h2 class="h4" id="checkout-account-heading">Your Account</h2>
                <p class="mb-0">
                    Signed in as <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).
                    <a href="{{ route('account.dashboard') }}">Manage your account</a>
                </p>
            </section>
        @else
            @php
                $accountTab = old('account_action') === 'register'
                    ? 'create'
                    : (old('account_action') === 'signin' ? 'signin' : 'guest');
            @endphp
            <section class="checkout-card mb-4" aria-labelledby="checkout-account-heading">
                <h2 class="h4" id="checkout-account-heading">Your Account</h2>
                <p class="text-muted">Continue as a guest, sign in to your account, or create an account for faster checkout next time.</p>

                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $accountTab === 'guest' ? 'active' : '' }}" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#checkout-account-guest" aria-controls="checkout-account-guest" aria-selected="{{ $accountTab === 'guest' ? 'true' : 'false' }}">Continue as Guest</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $accountTab === 'signin' ? 'active' : '' }}" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#checkout-account-signin" aria-controls="checkout-account-signin" aria-selected="{{ $accountTab === 'signin' ? 'true' : 'false' }}">Sign In</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $accountTab === 'create' ? 'active' : '' }}" type="button" role="tab" data-bs-toggle="tab" data-bs-target="#checkout-account-create" aria-controls="checkout-account-create" aria-selected="{{ $accountTab === 'create' ? 'true' : 'false' }}">Create an Account</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade {{ $accountTab === 'guest' ? 'show active' : '' }}" id="checkout-account-guest" role="tabpanel">
                        <p class="mb-0">No account is required. You can complete checkout as a guest.</p>
                    </div>

                    <div class="tab-pane fade {{ $accountTab === 'signin' ? 'show active' : '' }}" id="checkout-account-signin" role="tabpanel">
                        <form method="POST" action="{{ route('customer.login.store') }}" class="row g-3">
                            @csrf
                            <input type="hidden" name="return_to" value="checkout">
                            <input type="hidden" name="account_action" value="signin">
                            <div class="col-md-6">
                                <label for="checkout_login_email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="checkout_login_email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="checkout_login_password" class="form-label">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="checkout_login_password" name="password" autocomplete="current-password" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="checkout_login_remember" value="1">
                                    <label class="form-check-label" for="checkout_login_remember">Remember me</label>
                                </div>
                                <button type="submit" class="btn btn-brand">Sign In</button>
                            </div>
                            <div class="col-12">
                                <a href="{{ route('customer.password.request') }}" class="small">Forgot your password?</a>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade {{ $accountTab === 'create' ? 'show active' : '' }}" id="checkout-account-create" role="tabpanel">
                        <form method="POST" action="{{ route('customer.register.store') }}" class="row g-3">
                            @csrf
                            <input type="hidden" name="return_to" value="checkout">
                            <input type="hidden" name="account_action" value="register">
                            <div class="col-md-6">
                                <label for="checkout_account_name" class="form-label">Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="checkout_account_name" name="name" value="{{ old('name', old('customer_name')) }}" autocomplete="name" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="checkout_account_email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="checkout_account_email" name="email" value="{{ old('email', old('customer_email')) }}" autocomplete="email" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="checkout_account_password" class="form-label">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="checkout_account_password" name="password" autocomplete="new-password" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="checkout_account_password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="checkout_account_password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                            </div>
                            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <span class="small text-muted">Already have an account?
                                    <button type="button" class="btn btn-link btn-sm p-0 align-baseline" data-bs-toggle="tab" data-bs-target="#checkout-account-signin">Sign in</button>
                                </span>
                                <button type="submit" class="btn btn-brand">Create Account</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        @endauth

        @if(! $stripeConfigured)
            <div class="alert alert-warning" role="alert">
                Online payment is not configured. Checkout will be available once Stripe credentials are set.
            </div>
        @elseif($stripeTestMode)
            <div class="alert alert-info" role="status">
                Stripe test mode is active. No real card will be charged.
            </div>
        @endif

        <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form" novalidate>
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

            <div class="row g-4">
                <div class="col-lg-7">
                    <section class="checkout-card">
                        <h2 class="h4">Contact</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="customer_name" class="form-label">Full name</label>
                                <input type="text" class="form-control @error('customer_name') is-invalid @enderror" id="customer_name" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" autocomplete="name" required>
                                @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="customer_email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('customer_email') is-invalid @enderror" id="customer_email" name="customer_email" value="{{ old('customer_email', auth()->user()?->email) }}" autocomplete="email" required>
                                @error('customer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="customer_phone" class="form-label">Phone <span class="text-muted">(optional)</span></label>
                                <input type="text" class="form-control @error('customer_phone') is-invalid @enderror" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" autocomplete="tel">
                                @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="checkout-card">
                        <h2 class="h4">Fulfillment</h2>

                        @if($errors->has('fulfillment_method'))
                            <div class="alert alert-danger py-2">{{ $errors->first('fulfillment_method') }}</div>
                        @endif

                        @if(! $setting->shipping_enabled && ! $setting->local_delivery_enabled && ! $setting->pickup_enabled)
                            <div class="alert alert-warning py-2 mb-0">No fulfillment methods are currently available. Please check back soon.</div>
                        @else
                            <div class="checkout-methods" role="radiogroup" aria-label="Fulfillment method">
                                @if($setting->shipping_enabled)
                                    <label class="checkout-method">
                                        <input type="radio" name="fulfillment_method" value="shipping" @checked(old('fulfillment_method', $defaultMethod) === 'shipping')>
                                        <span>
                                            <strong>Shipping</strong>
                                            <small>Delivered to your address.</small>
                                        </span>
                                    </label>
                                @endif
                                @if($setting->local_delivery_enabled)
                                    <label class="checkout-method">
                                        <input type="radio" name="fulfillment_method" value="local_delivery" @checked(old('fulfillment_method', $defaultMethod) === 'local_delivery')>
                                        <span>
                                            <strong>Local Delivery</strong>
                                            <small>Available in supported ZIP codes.</small>
                                        </span>
                                    </label>
                                @endif
                                @if($setting->pickup_enabled)
                                    <label class="checkout-method">
                                        <input type="radio" name="fulfillment_method" value="pickup" @checked(old('fulfillment_method', $defaultMethod) === 'pickup')>
                                        <span>
                                            <strong>Pickup</strong>
                                            <small>Collect your order at a pickup location.</small>
                                        </span>
                                    </label>
                                @endif
                            </div>

                            <div id="fulfillment-address-panel" class="mt-4">
                                <h3 class="h5">Fulfillment address</h3>
                                @if($addresses->isNotEmpty())
                                    <div class="mb-3">
                                        <label for="fulfillment_address_id" class="form-label">Use a saved address <span class="text-muted">(optional)</span></label>
                                        <select name="fulfillment_address_id" id="fulfillment_address_id" class="form-select">
                                            <option value="">Enter a new address</option>
                                            @foreach($addresses as $address)
                                                <option value="{{ $address->id }}" @selected((string) old('fulfillment_address_id') === (string) $address->id)>
                                                    {{ $address->label ?: 'Address' }} — {{ $address->address_line_1 }}, {{ $address->city }} {{ $address->postal_code }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="form-text">When a saved address is selected, the new-address fields below are ignored.</p>
                                    </div>
                                @endif
                                @include('checkout._address-fields', ['prefix' => 'fulfillment'])
                                <p id="fulfillment-note" class="checkout-note" role="status"></p>
                            </div>

                            <div id="pickup-panel" class="mt-4 d-none">
                                <h3 class="h5">Pickup location</h3>
                                @if($pickupLocations->isEmpty())
                                    <div class="alert alert-warning py-2 mb-0">No pickup locations are currently available.</div>
                                @else
                                    <div class="checkout-pickup-list">
                                        @foreach($pickupLocations as $location)
                                            <label class="checkout-pickup">
                                                <input type="radio" name="pickup_location_id" value="{{ $location->id }}" @checked((string) old('pickup_location_id') === (string) $location->id)>
                                                <span>
                                                    <strong>{{ $location->name }}</strong>
                                                    <small>{{ $location->address }}</small>
                                                    @if($location->fee_cents > 0)<small>Pickup fee: ${{ number_format($location->fee_cents / 100, 2) }}</small>@endif
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                                @error('pickup_location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        @endif
                    </section>

                    <section class="checkout-card">
                        <h2 class="h4">Billing address</h2>

                        <div id="billing-same-wrapper">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="billing_same_as_fulfillment" id="billing_same_as_fulfillment" value="1" @checked(old('billing_same_as_fulfillment', true))>
                                <label class="form-check-label" for="billing_same_as_fulfillment">Billing address is the same as the fulfillment address</label>
                            </div>
                        </div>

                        <div id="billing-address-panel">
                            @if($addresses->isNotEmpty())
                                <div class="mb-3">
                                    <label for="billing_address_id" class="form-label">Use a saved billing address <span class="text-muted">(optional)</span></label>
                                    <select name="billing_address_id" id="billing_address_id" class="form-select">
                                        <option value="">Enter a new billing address</option>
                                        @foreach($addresses as $address)
                                            <option value="{{ $address->id }}" @selected((string) old('billing_address_id') === (string) $address->id)>
                                                {{ $address->label ?: 'Address' }} — {{ $address->address_line_1 }}, {{ $address->city }} {{ $address->postal_code }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @include('checkout._address-fields', ['prefix' => 'billing'])
                        </div>
                    </section>

                    @if($saveCardAvailable && $stripeConfigured)
                        <section class="checkout-card">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="save_payment_method" id="save_payment_method" value="1" @checked(old('save_payment_method'))>
                                <label class="form-check-label" for="save_payment_method">Save this card for future Sograte purchases</label>
                            </div>
                            <p class="form-text mb-0">Saving is optional. Your card details are handled by Stripe.</p>
                        </section>
                    @endif

                    <p class="text-muted small mb-0">Payment is processed securely by Stripe. Sograte does not store card details.</p>
                </div>

                <div class="col-lg-5">
                    <aside class="checkout-summary">
                        <h2 class="h4">Order Summary</h2>

                        <div class="checkout-summary__items">
                            @foreach($cart->items as $item)
                                <div class="checkout-summary__item">
                                    <span>{{ $item->variant->product->name }} — {{ $item->variant->name }} × {{ $item->quantity }}</span>
                                    <strong>{{ $item->line_subtotal_formatted }}</strong>
                                </div>
                            @endforeach
                        </div>

                        <div class="checkout-summary__row">
                            <span>Subtotal ({{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }})</span>
                            <strong>{{ '$'.number_format($subtotal / 100, 2) }}</strong>
                        </div>
                        <div class="checkout-summary__row">
                            <span>Fulfillment</span>
                            <strong id="checkout-fulfillment">$0.00</strong>
                        </div>
                        <div class="checkout-summary__row">
                            <span>Tax</span>
                            <strong>$0.00</strong>
                        </div>
                        <div class="checkout-summary__row checkout-summary__total">
                            <span>Total</span>
                            <strong id="checkout-total">{{ '$'.number_format($subtotal / 100, 2) }}</strong>
                        </div>
                        <p id="checkout-note" class="checkout-note" role="status"></p>

                        <div id="payment-section" class="checkout-payment d-none">
                            <h3 class="h5">Payment</h3>
                            <div id="payment-element"></div>
                            <div id="payment-message" class="checkout-note" role="alert"></div>
                        </div>

                        <button type="button" id="checkout-payment-button" class="btn btn-brand w-100 mt-3" @disabled(! $stripeConfigured)>
                            {{ $stripeConfigured ? 'Continue to Payment' : 'Payment Unavailable' }}
                        </button>
                        <a href="{{ route('cart.index') }}" class="btn btn-brand-outline w-100 mt-2">Back to Cart</a>
                    </aside>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@section('scripts')
@if($stripeConfigured)
<script src="https://js.stripe.com/v3/"></script>
@endif
@php
    $checkoutData = [
        'subtotal_cents' => $subtotal,
        'item_count' => $itemCount,
        'value' => round($subtotal / 100, 2),
        'items' => $gaItems,
        'shipping_basis' => $setting->shipping_basis->value,
        'shipping_rules' => $shippingRules->map(fn ($rule) => [
            'min_value' => $rule->min_value,
            'max_value' => $rule->max_value,
            'fee_cents' => $rule->fee_cents,
        ])->all(),
        'delivery_zones' => $deliveryZones->map(fn ($zone) => [
            'zip_code' => $zone->zip_code,
            'fee_cents' => $zone->fee_cents,
        ])->all(),
        'local_delivery_threshold_enabled' => $setting->local_delivery_threshold_enabled,
        'local_delivery_threshold_cents' => $setting->local_delivery_threshold_cents,
        'local_delivery_flat_fee_cents' => $setting->local_delivery_flat_fee_cents,
        'pickup_locations' => $pickupLocations->map(fn ($location) => [
            'id' => $location->id,
            'fee_cents' => $location->fee_cents,
        ])->all(),
    ];
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkout = @json($checkoutData);

    function syncAccountField(source, target) {
        if (!source || !target) {
            return;
        }

        if (target.value !== '') {
            target.dataset.userEdited = 'true';
        }

        source.addEventListener('input', function () {
            if (target.dataset.userEdited !== 'true') {
                target.value = source.value;
            }
        });

        target.addEventListener('input', function () {
            target.dataset.userEdited = 'true';
        });
    }

    syncAccountField(document.getElementById('customer_name'), document.getElementById('checkout_account_name'));
    syncAccountField(document.getElementById('customer_email'), document.getElementById('checkout_account_email'));

    const fulfillmentPanel = document.getElementById('fulfillment-address-panel');
    const pickupPanel = document.getElementById('pickup-panel');
    const billingPanel = document.getElementById('billing-address-panel');
    const billingSameWrapper = document.getElementById('billing-same-wrapper');
    const billingSame = document.getElementById('billing_same_as_fulfillment');
    const checkoutForm = document.getElementById('checkout-form');
    const paymentButton = document.getElementById('checkout-payment-button');
    const paymentSection = document.getElementById('payment-section');
    const paymentMessage = document.getElementById('payment-message');
    const paymentConfigured = @json($stripeConfigured);
    const stripePublishableKey = @json($stripePublishableKey);
    let stripe = null;
    let elements = null;
    let paymentElement = null;
    let paymentIntentData = null;
    let paymentProcessing = false;

    function money(cents) {
        return '$' + (cents / 100).toFixed(2);
    }

    function selectedMethod() {
        return document.querySelector('input[name="fulfillment_method"]:checked')?.value || null;
    }

    function trackAddShippingInfo() {
        if (typeof window.gtag !== 'function') {
            return;
        }

        const method = selectedMethod();
        const tiers = {
            shipping: 'Shipping',
            local_delivery: 'Local Delivery',
            pickup: 'Pickup',
        };

        window.gtag('event', 'add_shipping_info', {
            currency: 'USD',
            value: checkout.value,
            shipping_tier: tiers[method] || 'Fulfillment',
            items: checkout.items,
        });
    }

    function updateCheckout() {
        const method = selectedMethod();
        const note = document.getElementById('checkout-note');
        const fulfillmentNote = document.getElementById('fulfillment-note');
        let fee = 0;
        let message = '';

        fulfillmentPanel?.classList.toggle('d-none', method === 'pickup');
        pickupPanel?.classList.toggle('d-none', method !== 'pickup');
        billingSameWrapper?.classList.toggle('d-none', method === 'pickup');
        billingPanel?.classList.toggle('d-none', method !== 'pickup' && billingSame?.checked);

        if (method === 'shipping') {
            const value = checkout.shipping_basis === 'subtotal' ? checkout.subtotal_cents : checkout.item_count;
            const rule = checkout.shipping_rules.find((item) => value >= item.min_value && value <= item.max_value);
            if (rule) {
                fee = rule.fee_cents;
            } else {
                message = 'Shipping is not available for this order total.';
            }
            if (fulfillmentNote) fulfillmentNote.textContent = message;
        } else if (method === 'local_delivery') {
            const zip = (document.getElementById('fulfillment_postal_code')?.value || '').trim().toUpperCase();
            const zone = checkout.delivery_zones.find((item) => item.zip_code === zip);
            if (zone) {
                fee = checkout.local_delivery_threshold_enabled
                    && checkout.subtotal_cents <= checkout.local_delivery_threshold_cents
                    ? checkout.local_delivery_flat_fee_cents
                    : zone.fee_cents;
            } else {
                message = zip ? 'Local delivery is not available for this ZIP code.' : 'Enter your ZIP code to see local delivery availability.';
            }
            if (fulfillmentNote) fulfillmentNote.textContent = message;
        } else if (method === 'pickup') {
            const selected = document.querySelector('input[name="pickup_location_id"]:checked');
            const location = checkout.pickup_locations.find((item) => String(item.id) === selected?.value);
            if (location) {
                fee = location.fee_cents;
            } else if (checkout.pickup_locations.length > 0) {
                message = 'Select a pickup location.';
            }
            if (fulfillmentNote) fulfillmentNote.textContent = '';
        } else {
            if (fulfillmentNote) fulfillmentNote.textContent = '';
        }

        document.getElementById('checkout-fulfillment').textContent = money(fee);
        document.getElementById('checkout-total').textContent = money(checkout.subtotal_cents + fee);
        note.textContent = message;
    }

    document.querySelectorAll('input[name="fulfillment_method"], input[name="pickup_location_id"]').forEach((input) => {
        input.addEventListener('change', updateCheckout);
    });

    billingSame?.addEventListener('change', updateCheckout);
    document.getElementById('fulfillment_postal_code')?.addEventListener('input', updateCheckout);

    function setPaymentMessage(message) {
        if (paymentMessage) {
            paymentMessage.textContent = message || '';
        }
    }

    function setDetailFieldsDisabled(disabled) {
        checkoutForm.querySelectorAll('input, select, textarea').forEach((field) => {
            if (field.name !== '_token' && field.name !== 'checkout_token') {
                field.disabled = disabled;
            }
        });
    }

    async function beginPayment() {
        if (!paymentConfigured || paymentProcessing) {
            return;
        }

        if (!checkoutForm.reportValidity()) {
            return;
        }

        paymentProcessing = true;
        paymentButton.disabled = true;
        paymentButton.textContent = 'Preparing secure payment...';
        setPaymentMessage('');

        try {
            const response = await fetch(@json(route('checkout.store')), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': checkoutForm.querySelector('input[name="_token"]').value,
                },
                body: new FormData(checkoutForm),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = data.errors ? Object.values(data.errors).flat()[0] : (data.message || 'Checkout could not be prepared. Please review your details.');
                throw new Error(message);
            }

            if (data.checkout_token) {
                checkoutForm.querySelector('input[name="checkout_token"]').value = data.checkout_token;
            }

            if (data.already_paid && data.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }

            trackAddShippingInfo();

            if (!data.client_secret || typeof Stripe === 'undefined') {
                throw new Error('Secure payment could not be initialized.');
            }

            paymentIntentData = data;
            stripe = Stripe(data.publishable_key || stripePublishableKey);

            const elementOptions = {
                clientSecret: data.client_secret,
                appearance: { theme: 'stripe' },
            };

            if (data.customer_session_client_secret) {
                elementOptions.customerSessionClientSecret = data.customer_session_client_secret;
            }

            elements = stripe.elements(elementOptions);
            paymentElement = elements.create('payment', { layout: 'tabs' });
            paymentElement.mount('#payment-element');
            paymentElement.on('change', (event) => {
                if (event.error) {
                    setPaymentMessage(event.error.message);
                }
            });

            paymentSection.classList.remove('d-none');
            setDetailFieldsDisabled(true);
            document.getElementById('checkout-total').textContent = data.total_formatted;
            paymentButton.textContent = 'Pay ' + data.total_formatted + ' securely';
            paymentButton.disabled = false;
            paymentProcessing = false;
            paymentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (error) {
            setPaymentMessage(error.message || 'Secure payment could not be initialized. Please try again.');
            paymentButton.textContent = 'Retry Payment Setup';
            paymentButton.disabled = false;
            paymentProcessing = false;
        }
    }

    async function pay() {
        if (!stripe || !elements || !paymentIntentData || paymentProcessing) {
            return;
        }

        paymentProcessing = true;
        paymentButton.disabled = true;
        paymentButton.textContent = 'Processing payment...';
        setPaymentMessage('');

        try {
            const result = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: paymentIntentData.return_url,
                },
                redirect: 'if_required',
            });

            if (result.error) {
                setPaymentMessage(result.error.message || 'Payment could not be completed. Please try again.');
                paymentButton.textContent = 'Try Payment Again';
                paymentButton.disabled = false;
                paymentProcessing = false;

                return;
            }

            window.location.href = paymentIntentData.return_url;
        } catch (error) {
            setPaymentMessage(error.message || 'Payment could not be completed. Please try again.');
            paymentButton.textContent = 'Try Payment Again';
            paymentButton.disabled = false;
            paymentProcessing = false;
        }
    }

    paymentButton?.addEventListener('click', () => {
        if (paymentIntentData) {
            pay();
        } else {
            beginPayment();
        }
    });

    updateCheckout();
});
</script>
@endsection
