<?php

namespace Tests\Support;

use App\Models\Payment;
use App\Models\User;
use App\Services\StripeGateway;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use RuntimeException;

class FakeStripeGateway extends StripeGateway
{
    public bool $isConfigured = true;

    public string $intentStatus = 'requires_payment_method';

    public bool $intentLivemode = false;

    public string $refundStatus = 'succeeded';

    public array $createdIntents = [];

    public array $createdIntentKeys = [];

    public array $intents = [];

    public array $savedMethods = [];

    public array $setupIntents = [];

    public array $refunds = [];

    public array $detachedMethods = [];

    public ?object $webhookEvent = null;

    public function configured(): bool
    {
        return $this->isConfigured;
    }

    public function publishableKey(): ?string
    {
        return 'pk_test_fake';
    }

    public function isTestMode(): bool
    {
        return true;
    }

    public function ensureConfigured(): void
    {
        if (! $this->isConfigured) {
            throw new RuntimeException('Stripe is not configured.');
        }
    }

    public function createPaymentIntent(array $params, string $idempotencyKey): object
    {
        $this->createdIntents[] = $params;
        $this->createdIntentKeys[] = $idempotencyKey;

        $id = 'pi_test_'.Str::lower(Str::random(12));
        $intent = (object) [
            'id' => $id,
            'status' => $this->intentStatus,
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'client_secret' => 'pi_secret_'.$id,
            'livemode' => $this->intentLivemode,
            'metadata' => (object) ($params['metadata'] ?? []),
            'last_payment_error' => null,
        ];

        $this->intents[$id] = $intent;

        return $intent;
    }

    public function retrievePaymentIntent(string $paymentIntentId): object
    {
        if (! isset($this->intents[$paymentIntentId])) {
            throw new RuntimeException('Payment intent not found.');
        }

        return $this->intents[$paymentIntentId];
    }

    public function cancelPaymentIntent(string $paymentIntentId): object
    {
        if (! isset($this->intents[$paymentIntentId])) {
            throw new RuntimeException('Payment intent not found.');
        }

        $this->intents[$paymentIntentId]->status = 'canceled';

        return $this->intents[$paymentIntentId];
    }

    public function createCustomer(User $user): string
    {
        return 'cus_test_'.$user->id;
    }

    public function createCustomerSession(string $customerId): string
    {
        return 'cs_test_secret_'.$customerId;
    }

    public function createSetupIntent(string $customerId, string $idempotencyKey): object
    {
        $this->setupIntents[] = [
            'customer' => $customerId,
            'idempotency_key' => $idempotencyKey,
        ];

        $id = 'seti_test_'.Str::lower(Str::random(12));

        return (object) [
            'id' => $id,
            'client_secret' => $id.'_secret_'.$id,
        ];
    }

    public function listSavedPaymentMethods(User $user): array
    {
        return $this->savedMethods;
    }

    public function detachSavedPaymentMethod(User $user, string $paymentMethodId): void
    {
        if (str_starts_with($paymentMethodId, 'other_')) {
            throw new AuthorizationException;
        }

        $this->detachedMethods[] = $paymentMethodId;
    }

    public function createRefund(Payment $payment, int $amountCents, string $idempotencyKey): object
    {
        $refund = (object) [
            'id' => 're_test_'.Str::lower(Str::random(12)),
            'payment_intent' => $payment->payment_intent_id,
            'amount' => $amountCents,
            'currency' => strtolower($payment->currency),
            'status' => $this->refundStatus,
            'failure_reason' => null,
        ];

        $this->refunds[] = $refund;

        return $refund;
    }

    public function constructWebhookEvent(string $payload, string $signature): object
    {
        if (! $this->webhookEvent) {
            throw new RuntimeException('No fake webhook event configured.');
        }

        return $this->webhookEvent;
    }
}
