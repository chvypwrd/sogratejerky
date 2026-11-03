<?php

namespace App\Services;

use App\Models\DeletedTestOrder;
use App\Models\DeletedTestOrderPaymentIntent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class TestOrderCleanupService
{
    public function __construct(
        private StripeGateway $gateway,
    ) {}

    public function inspect(Order $order, bool $allowRemoteVerification = true): array
    {
        $order->loadMissing('payments');

        $counts = [
            'order_items' => $order->items()->count(),
            'payments' => $order->payments()->count(),
            'refunds' => PaymentRefund::where('order_id', $order->id)->count(),
            'activities' => $order->activities()->count(),
        ];

        if (! $order->is_test_order) {
            return $this->ineligible('Order is not classified as a test order.', $counts);
        }

        if ($order->payments->isEmpty()) {
            return $this->ineligible('Order has no payment record to verify.', $counts);
        }

        $paymentIntentIds = [];
        $remoteVerificationBasis = null;

        foreach ($order->payments as $payment) {
            if (! $payment->payment_intent_id) {
                return $this->ineligible('Order has a payment without a Stripe PaymentIntent.', $counts);
            }

            $livemode = $payment->stripe_livemode;

            if ($livemode === null) {
                if (! $allowRemoteVerification) {
                    return $this->ineligible('Stripe livemode evidence is missing.', $counts);
                }

                try {
                    $verification = $this->gateway->retrievePaymentIntentForTestModeVerification($payment->payment_intent_id);
                    $intent = $verification['intent'];
                } catch (Throwable $exception) {
                    report($exception);

                    return $this->ineligible('Stripe payment verification is unavailable or inconclusive.', $counts);
                }

                $livemode = data_get($intent, 'livemode');

                if (! is_bool($livemode)) {
                    return $this->ineligible('Stripe payment verification did not return a definitive mode.', $counts);
                }

                if (! $this->intentMatchesPayment($intent, $payment)) {
                    return $this->ineligible('Stripe PaymentIntent does not match the stored payment record.', $counts);
                }

                $payment->update(['stripe_livemode' => $livemode]);
                $remoteVerificationBasis = $verification['credential'] === 'test'
                    ? 'read_only_stripe_test_key'
                    : 'read_only_stripe_current_key';
            }

            if ($livemode !== false) {
                return $this->ineligible('Order contains a live Stripe payment.', $counts);
            }

            $paymentIntentIds[] = (string) $payment->payment_intent_id;
        }

        $paymentIntentIds = array_values(array_unique($paymentIntentIds));

        if (DeletedTestOrderPaymentIntent::whereIn('payment_intent_id', $paymentIntentIds)->exists()) {
            return $this->ineligible('A PaymentIntent is already recorded as deleted.', $counts);
        }

        return [
            'eligible' => true,
            'reason' => null,
            'counts' => $counts,
            'payment_intent_ids' => $paymentIntentIds,
            'verification_basis' => $remoteVerificationBasis ?? 'local_stripe_livemode',
        ];
    }

    public function delete(Order $order, User $actor): DeletedTestOrder
    {
        $inspection = $this->inspect($order, true);

        if (! $inspection['eligible']) {
            throw ValidationException::withMessages([
                'order' => $inspection['reason'],
            ]);
        }

        return DB::transaction(function () use ($order, $actor) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $inspection = $this->inspect($lockedOrder, false);

            if (! $inspection['eligible']) {
                throw ValidationException::withMessages([
                    'order' => $inspection['reason'],
                ]);
            }

            $audit = DeletedTestOrder::create([
                'original_order_id' => $lockedOrder->id,
                'order_number' => $lockedOrder->order_number,
                'original_total_cents' => $lockedOrder->total_cents,
                'original_placed_at' => $lockedOrder->placed_at,
                'verification_basis' => $inspection['verification_basis'],
                'record_counts' => $inspection['counts'],
                'deleted_by' => $actor->id,
                'deleted_by_name' => $actor->name,
                'deleted_at' => now(),
            ]);

            foreach ($inspection['payment_intent_ids'] as $paymentIntentId) {
                DeletedTestOrderPaymentIntent::create([
                    'deleted_test_order_id' => $audit->id,
                    'payment_intent_id' => $paymentIntentId,
                ]);
            }

            $lockedOrder->items()->delete();
            PaymentRefund::where('order_id', $lockedOrder->id)->delete();
            $lockedOrder->payments()->delete();
            $lockedOrder->activities()->delete();
            $lockedOrder->delete();

            return $audit;
        });
    }

    private function intentMatchesPayment(object $intent, Payment $payment): bool
    {
        if ((string) data_get($intent, 'id') !== (string) $payment->payment_intent_id) {
            return false;
        }

        if ((int) data_get($intent, 'amount') !== (int) $payment->amount_cents) {
            return false;
        }

        if (strtoupper((string) data_get($intent, 'currency')) !== strtoupper((string) $payment->currency)) {
            return false;
        }

        $metadataOrderId = data_get($intent, 'metadata.order_id');

        return $metadataOrderId === null || (string) $metadataOrderId === (string) $payment->order_id;
    }

    private function ineligible(string $reason, array $counts): array
    {
        return [
            'eligible' => false,
            'reason' => $reason,
            'counts' => $counts,
            'payment_intent_ids' => [],
            'verification_basis' => null,
        ];
    }
}
