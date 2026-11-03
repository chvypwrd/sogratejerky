<?php

namespace App\Services;

use App\Models\DeletedTestOrder;
use App\Models\DeletedTestOrderPaymentIntent;
use App\Models\Order;
use App\Models\PaymentRefund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderDeletionService
{
    public function inspectClassified(Order $order): array
    {
        return $this->inspect($order, true);
    }

    public function inspectLegacy(Order $order): array
    {
        return $this->inspect($order, false);
    }

    public function delete(Order $order, User $actor): DeletedTestOrder
    {
        return $this->deleteOrder($order, $actor, true, null);
    }

    public function deleteLegacy(Order $order, User $actor, ?string $overrideReason): DeletedTestOrder
    {
        return $this->deleteOrder($order, $actor, false, $overrideReason);
    }

    private function inspect(Order $order, bool $classified): array
    {
        $order->loadMissing('payments.refunds');

        $counts = [
            'order_items' => $order->items()->count(),
            'payments' => $order->payments()->count(),
            'refunds' => PaymentRefund::where('order_id', $order->id)->count(),
            'activities' => $order->activities()->count(),
        ];

        $evidence = $this->paymentEvidence($order);

        if ($classified && ! $order->is_test_order) {
            return $this->ineligible('Order is not explicitly classified as a historical test order.', $counts, $evidence);
        }

        if (! $classified && $order->is_test_order) {
            return $this->ineligible('Order is already classified as a test order. Use Delete Order instead.', $counts, $evidence);
        }

        if ($order->payments->contains(fn ($payment) => $payment->stripe_livemode === true)) {
            return $this->ineligible('Order contains a locally recorded live-mode Stripe payment.', $counts, $evidence);
        }

        $requiresOverride = ! $classified
            && ($order->payments->isEmpty() || $order->payments->contains(fn ($payment) => $payment->stripe_livemode === null));

        return [
            'eligible' => true,
            'requires_override' => $requiresOverride,
            'reason' => null,
            'counts' => $counts,
            'evidence' => $evidence,
            'payment_intent_ids' => $order->payments
                ->pluck('payment_intent_id')
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];
    }

    private function deleteOrder(Order $order, User $actor, bool $classified, ?string $overrideReason): DeletedTestOrder
    {
        $inspection = $classified ? $this->inspectClassified($order) : $this->inspectLegacy($order);
        $this->assertEligible($inspection, $overrideReason);

        return DB::transaction(function () use ($order, $actor, $classified, $overrideReason) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $inspection = $classified ? $this->inspectClassified($lockedOrder) : $this->inspectLegacy($lockedOrder);
            $this->assertEligible($inspection, $overrideReason);

            $basis = $classified
                ? 'administrator_confirmed_local_test_order'
                : ($inspection['requires_override'] ? 'administrator_override_unverified_payment_mode' : 'local_stripe_livemode_false');

            $audit = DeletedTestOrder::create([
                'original_order_id' => $lockedOrder->id,
                'order_number' => $lockedOrder->order_number,
                'original_total_cents' => $lockedOrder->total_cents,
                'original_placed_at' => $lockedOrder->placed_at,
                'verification_basis' => $basis,
                'local_payment_evidence' => $inspection['evidence'],
                'override_reason' => $inspection['requires_override'] ? trim((string) $overrideReason) : null,
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

    private function assertEligible(array $inspection, ?string $overrideReason): void
    {
        if (! $inspection['eligible']) {
            throw ValidationException::withMessages([
                'order' => $inspection['reason'],
            ]);
        }

        if ($inspection['requires_override'] && trim((string) $overrideReason) === '') {
            throw ValidationException::withMessages([
                'override_reason' => 'Provide the administrator override reason for this ambiguous legacy order.',
            ]);
        }
    }

    private function paymentEvidence(Order $order): array
    {
        return $order->payments
            ->map(fn ($payment) => [
                'payment_id' => $payment->id,
                'payment_intent_id' => $payment->payment_intent_id,
                'status' => $payment->status?->value,
                'stripe_livemode' => $payment->stripe_livemode,
                'amount_cents' => $payment->amount_cents,
                'currency' => $payment->currency,
                'refund_count' => $payment->refunds->count(),
            ])
            ->values()
            ->all();
    }

    private function ineligible(string $reason, array $counts, array $evidence): array
    {
        return [
            'eligible' => false,
            'requires_override' => false,
            'reason' => $reason,
            'counts' => $counts,
            'evidence' => $evidence,
            'payment_intent_ids' => [],
        ];
    }
}
