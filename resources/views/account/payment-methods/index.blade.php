@extends('layouts.app')

@section('title', 'Payment Methods')

@section('meta')
    <meta name="robots" content="noindex,nofollow">
@endsection

@section('content')
<section class="py-5">
    <div class="container">
        <header class="page-header">
            <span class="eyebrow">Account</span>
            <h1>Payment Methods</h1>
            <p class="lead text-muted">Manage cards saved securely with Stripe.</p>
        </header>

        @include('account._nav')

        @if(session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
        @endif

        @if($error)
            <div class="alert alert-warning" role="status">{{ $error }}</div>
        @endif

        @if(empty($methods))
            <div class="card">
                <div class="card-header"><strong>Add a payment method</strong></div>
                <div class="card-body">
                    <p class="text-muted">You do not have any saved payment methods yet. Add a card below to save it securely with Stripe.</p>

                    @if($setupClientSecret && $publishableKey)
                        <div id="payment-element" class="mb-3"></div>
                        <div id="payment-message" class="text-danger small mb-3" role="alert"></div>
                        <button type="button" id="save-payment-method" class="btn btn-brand">Save Payment Method</button>
                        <p class="small text-muted mt-3 mb-0">Your card details are handled securely by Stripe. Sograte does not store card details.</p>
                    @else
                        <a href="{{ route('shop.index') }}" class="btn btn-brand">Continue Shopping</a>
                    @endif
                </div>
            </div>
        @else
            <div class="card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Card</th>
                                <th>Expires</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($methods as $method)
                                <tr>
                                    <td class="text-capitalize">{{ $method['brand'] }} ending in {{ $method['last4'] }}</td>
                                    <td>{{ $method['exp_month'] }}/{{ $method['exp_year'] }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('account.payment-methods.destroy', $method['id']) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this saved payment method?')">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

@if($setupClientSecret && $publishableKey)
@section('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const stripe = Stripe(@json($publishableKey));
    const elements = stripe.elements({ clientSecret: @json($setupClientSecret) });
    const paymentElement = elements.create('payment');
    const button = document.getElementById('save-payment-method');
    const message = document.getElementById('payment-message');
    const returnUrl = @json(route('account.payment-methods.index'));

    paymentElement.mount('#payment-element');
    paymentElement.on('change', function (event) {
        message.textContent = event.error ? event.error.message : '';
    });

    button.addEventListener('click', async function () {
        button.disabled = true;
        button.textContent = 'Saving...';
        message.textContent = '';

        const result = await stripe.confirmSetup({
            elements: elements,
            confirmParams: { return_url: returnUrl },
            redirect: 'if_required',
        });

        if (result.error) {
            message.textContent = result.error.message || 'The payment method could not be saved.';
            button.disabled = false;
            button.textContent = 'Save Payment Method';

            return;
        }

        window.location.href = returnUrl;
    });
});
</script>
@endsection
@endif
