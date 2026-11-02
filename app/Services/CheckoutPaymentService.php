<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutPaymentService
{
    public function __construct(
        private CheckoutCalculator $calculator,
        private OrderService $orderService,
        private StripeGateway $gateway,
        private PaymentReconciler $reconciler,
    ) {}

    public function start(Cart $cart, array $data, ?User $user, string $checkoutToken): array
    {
        $this->gateway->ensureConfigured();

        $summary = $this->calculator->calculate($cart, $data['fulfillment_method'], $data, $user);
        $order = Order::where('checkout_token', $checkoutToken)->first();

        if ($order && ! $order->belongsToCheckoutContext($cart, $user)) {
            throw ValidationException::withMessages([
                'checkout_token' => 'This checkout session is no longer valid. Please review your cart and try again.',
            ]);
        }

        if ($order && $order->isCancelled()) {
            throw ValidationException::withMessages([
                'checkout_token' => 'This checkout was cancelled. Please start a new checkout.',
            ]);
        }

        if ($order && $order->isTerminalCheckout()) {
            if (! $this->cartMatchesOrderContents($order, $cart)) {
                throw ValidationException::withMessages([
                    'checkout_token' => 'This checkout has already been completed. Please start a new checkout.',
                ]);
            }

            return $this->paidResponse($order);
        }

        $latestPayment = null;
        $intent = null;
        $payment = null;

        if ($order) {
            $latestPayment = $order->payments()->first();

            if ($latestPayment && $latestPayment->payment_intent_id) {
                try {
                    $intent = $this->gateway->retrievePaymentIntent($latestPayment->payment_intent_id);
                } catch (Throwable $exception) {
                    report($exception);
                    $intent = null;
                }

                if ($intent && (string) data_get($intent, 'status') === 'succeeded') {
                    $this->reconciler->applyPaymentIntent($latestPayment, $intent);

                    return $this->paidResponse($order->fresh());
                }

                if ($intent && (string) data_get($intent, 'status') === 'processing' && ! $this->intentMatchesSummary($intent, $summary)) {
                    throw ValidationException::withMessages([
                        'payment' => 'A payment is already processing. Please wait for it to complete before changing checkout details.',
                    ]);
                }

                if (! $intent || ! $this->intentMatchesSummary($intent, $summary) || ! $this->isReusableIntent($intent)) {
                    $this->cancelStaleIntent($latestPayment);
                    $latestPayment = null;
                    $intent = null;
                }
            }

            $order = $this->orderService->refreshPendingOrder($order, $cart, $data, $summary, $user);
        } else {
            $order = $this->orderService->createFromCheckout($cart, $data, $summary, $user, $checkoutToken);
        }

        $customerId = $this->resolveCustomer($user);

        if ($intent && $latestPayment && $this->intentMatchesOrder($intent, $order) && $this->isReusableIntent($intent)) {
            $payment = $latestPayment;
        } else {
            if ($latestPayment) {
                $this->cancelStaleIntent($latestPayment);
            }

            $attemptNumber = $order->payments()->count() + 1;
            $idempotencyKey = 'pi_'.hash('sha256', implode('|', [
                $order->id,
                $attemptNumber,
                $order->total_cents,
                $order->currency,
                $order->checkout_token,
            ]));

            $intent = $this->gateway->createPaymentIntent(
                $this->intentParams($order, $customerId, (bool) ($data['save_payment_method'] ?? false)),
                $idempotencyKey,
            );

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'provider' => 'stripe',
                'payment_intent_id' => data_get($intent, 'id'),
                'stripe_livemode' => data_get($intent, 'livemode'),
                'stripe_customer_id' => $customerId,
                'amount_cents' => $order->total_cents,
                'currency' => strtoupper($order->currency),
                'status' => PaymentStatus::Pending,
            ]);
        }

        if ((string) data_get($intent, 'status') === 'succeeded') {
            $this->reconciler->applyPaymentIntent($payment, $intent);

            return $this->paidResponse($order->fresh());
        }

        $this->syncPaymentAttempt($order, $payment, $intent);

        $customerSession = null;

        if ($customerId) {
            try {
                $customerSession = $this->gateway->createCustomerSession($customerId);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return [
            'order_number' => $order->order_number,
            'client_secret' => data_get($intent, 'client_secret'),
            'customer_session_client_secret' => $customerSession,
            'return_url' => $this->returnUrl($order),
            'publishable_key' => $this->gateway->publishableKey(),
            'total_cents' => $order->total_cents,
            'total_formatted' => $order->total_formatted,
        ];
    }

    private function resolveCustomer(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        $customerId = $this->gateway->createCustomer($user);
        $user->update(['stripe_customer_id' => $customerId]);

        return $customerId;
    }

    private function intentParams(Order $order, ?string $customerId, bool $savePaymentMethod): array
    {
        $params = [
            'amount' => $order->total_cents,
            'currency' => strtolower($order->currency),
            'description' => 'Sograte order '.$order->order_number,
            'receipt_email' => $order->customer_email,
            'metadata' => [
                'order_id' => (string) $order->id,
                'order_number' => $order->order_number,
            ],
        ];

        if ($customerId) {
            $params['customer'] = $customerId;
        }

        if ($customerId && $savePaymentMethod) {
            $params['setup_future_usage'] = 'off_session';
        }

        return $params;
    }

    private function syncPaymentAttempt(Order $order, Payment $payment, object $intent): void
    {
        $intentStatus = (string) data_get($intent, 'status');
        $status = $intentStatus === 'requires_payment_method' && ! data_get($intent, 'last_payment_error.code')
            ? PaymentStatus::Pending
            : $this->mapIntentStatus($intentStatus);

        $payment->update([
            'status' => $status,
            'stripe_livemode' => data_get($intent, 'livemode'),
            'error_code' => data_get($intent, 'last_payment_error.code'),
            'error_message' => data_get($intent, 'last_payment_error.message'),
            'canceled_at' => $intentStatus === 'canceled' ? now() : $payment->canceled_at,
        ]);

        if (! in_array($order->payment_status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded, PaymentStatus::Refunded], true)) {
            $order->update(['payment_status' => $status]);
        }
    }

    private function mapIntentStatus(string $status): PaymentStatus
    {
        return match ($status) {
            'succeeded' => PaymentStatus::Paid,
            'processing', 'requires_action', 'requires_confirmation' => PaymentStatus::Processing,
            'requires_payment_method' => PaymentStatus::Pending,
            'canceled' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
    }

    private function isReusableIntent(object $intent): bool
    {
        return in_array((string) data_get($intent, 'status'), [
            'requires_payment_method',
            'requires_confirmation',
            'requires_action',
            'processing',
        ], true);
    }

    private function intentMatchesOrder(object $intent, Order $order): bool
    {
        $metadataOrderId = data_get($intent, 'metadata.order_id');

        return (int) data_get($intent, 'amount') === $order->total_cents
            && strtoupper((string) data_get($intent, 'currency')) === strtoupper($order->currency)
            && ($metadataOrderId === null || (string) $metadataOrderId === (string) $order->id);
    }

    private function intentMatchesSummary(object $intent, array $summary): bool
    {
        return (int) data_get($intent, 'amount') === $summary['total_cents']
            && strtoupper((string) data_get($intent, 'currency')) === 'USD';
    }

    private function cartMatchesOrderContents(Order $order, Cart $cart): bool
    {
        $cart->loadMissing('items');

        if ($cart->items->isEmpty()) {
            return true;
        }

        $order->loadMissing('items');

        $cartQuantities = $cart->items
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (int) $items->sum('quantity'))
            ->sortKeys()
            ->all();

        $orderQuantities = $order->items
            ->whereNotNull('product_variant_id')
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (int) $items->sum('quantity'))
            ->sortKeys()
            ->all();

        return $cartQuantities === $orderQuantities;
    }

    private function cancelStaleIntent(Payment $payment): void
    {
        if ($payment->payment_intent_id) {
            try {
                $this->gateway->cancelPaymentIntent($payment->payment_intent_id);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $payment->update([
            'status' => PaymentStatus::Failed,
            'canceled_at' => now(),
            'error_message' => 'Superseded by updated checkout details.',
        ]);
    }

    private function paidResponse(Order $order): array
    {
        return [
            'already_paid' => true,
            'order_number' => $order->order_number,
            'redirect_url' => URL::temporarySignedRoute(
                'checkout.confirmation',
                now()->addDays(7),
                ['order' => $order->order_number],
            ),
        ];
    }

    private function returnUrl(Order $order): string
    {
        return URL::temporarySignedRoute(
            'checkout.return',
            now()->addDays(7),
            ['order' => $order->order_number],
        );
    }
}
