<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Stripe\Customer;
use Stripe\CustomerSession;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeGateway
{
    public function configured(): bool
    {
        return filled(config('services.stripe.key'))
            && filled(config('services.stripe.secret'));
    }

    public function publishableKey(): ?string
    {
        return config('services.stripe.key');
    }

    public function isTestMode(): bool
    {
        return str_starts_with((string) config('services.stripe.key'), 'pk_test_');
    }

    public function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw ValidationException::withMessages([
                'payment' => 'Online payment is not configured yet. Please contact SOGRATE.',
            ]);
        }

        $this->configure();
    }

    public function createPaymentIntent(array $params, string $idempotencyKey): object
    {
        $this->ensureConfigured();

        return PaymentIntent::create($params, [
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): object
    {
        $this->ensureConfigured();

        return PaymentIntent::retrieve($paymentIntentId);
    }

    public function cancelPaymentIntent(string $paymentIntentId): object
    {
        $this->ensureConfigured();

        return PaymentIntent::retrieve($paymentIntentId)->cancel();
    }

    public function createCustomer(User $user): string
    {
        $this->ensureConfigured();

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => ['user_id' => (string) $user->id],
        ], [
            'idempotency_key' => 'customer_user_'.$user->id,
        ]);

        return $customer->id;
    }

    public function createCustomerSession(string $customerId): string
    {
        $this->ensureConfigured();

        $session = CustomerSession::create([
            'customer' => $customerId,
            'components' => [
                'payment_element' => [
                    'enabled' => true,
                    'features' => [
                        'payment_method_redisplay' => 'enabled',
                        'payment_method_remove' => 'enabled',
                    ],
                ],
            ],
        ]);

        return $session->client_secret;
    }

    public function listSavedPaymentMethods(User $user): array
    {
        if (! $user->stripe_customer_id || ! $this->configured()) {
            return [];
        }

        $this->configure();

        $methods = PaymentMethod::all([
            'customer' => $user->stripe_customer_id,
            'type' => 'card',
            'limit' => 20,
        ]);

        return collect($methods->data)->map(function (PaymentMethod $method) {
            $card = $method->card;

            return [
                'id' => $method->id,
                'brand' => $card->brand ?? 'Card',
                'last4' => $card->last4 ?? '••••',
                'exp_month' => str_pad((string) ($card->exp_month ?? ''), 2, '0', STR_PAD_LEFT),
                'exp_year' => $card->exp_year ?? '',
            ];
        })->all();
    }

    public function detachSavedPaymentMethod(User $user, string $paymentMethodId): void
    {
        if (! $user->stripe_customer_id || ! $this->configured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Saved payment methods are not available.',
            ]);
        }

        $this->configure();

        $method = PaymentMethod::retrieve($paymentMethodId);
        $customer = is_object($method->customer) ? $method->customer->id : $method->customer;

        if ($customer !== $user->stripe_customer_id) {
            throw new AuthorizationException;
        }

        $method->detach();
    }

    public function createRefund(Payment $payment, int $amountCents, string $idempotencyKey): object
    {
        $this->ensureConfigured();

        return Refund::create([
            'payment_intent' => $payment->payment_intent_id,
            'amount' => $amountCents,
        ], [
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function constructWebhookEvent(string $payload, string $signature): object
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            throw ValidationException::withMessages([
                'webhook' => 'Stripe webhook signing secret is not configured.',
            ]);
        }

        return Webhook::constructEvent($payload, $signature, $secret);
    }

    private function configure(): void
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));
        Stripe::setApiVersion((string) config('services.stripe.api_version', '2026-10-28.endive'));
        Stripe::setAppInfo('SOGRATE', '1.0', config('app.url'));
    }
}
