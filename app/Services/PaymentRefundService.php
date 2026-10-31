<?php

namespace App\Services;

use App\Enums\OrderEvent;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentRefundService
{
    public function __construct(
        private StripeGateway $gateway,
        private PaymentReconciler $reconciler,
    ) {}

    public function request(Order $order, Payment $payment, User $admin, int $amountCents, ?string $reason, string $requestKey): PaymentRefund
    {
        $existing = PaymentRefund::where('request_key', $requestKey)->first();

        if ($existing) {
            if ($existing->order_id !== $order->id) {
                throw ValidationException::withMessages([
                    'request_key' => 'This refund request key is not valid for this order.',
                ]);
            }

            if ($existing->status === RefundStatus::Failed) {
                throw ValidationException::withMessages([
                    'request_key' => 'This refund request failed. Reload the order and try again.',
                ]);
            }

            return $existing;
        }

        $refund = DB::transaction(function () use ($order, $payment, $admin, $amountCents, $reason, $requestKey) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->order_id !== $order->id) {
                throw ValidationException::withMessages([
                    'refund' => 'The payment does not belong to this order.',
                ]);
            }

            if (! in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded], true)) {
                throw ValidationException::withMessages([
                    'refund' => 'Only captured payments can be refunded.',
                ]);
            }

            $reserved = (int) $payment->refunds()
                ->whereIn('status', [RefundStatus::Pending->value, RefundStatus::Succeeded->value])
                ->sum('amount_cents');

            $refundable = max(0, $payment->amount_cents - $reserved);

            if ($amountCents < 1 || $amountCents > $refundable) {
                throw ValidationException::withMessages([
                    'amount' => 'Refund amount must be between $0.01 and the remaining refundable balance.',
                ]);
            }

            $refund = PaymentRefund::create([
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'request_key' => $requestKey,
                'amount_cents' => $amountCents,
                'currency' => $payment->currency,
                'status' => RefundStatus::Pending,
                'reason' => $reason,
                'requested_by' => $admin->id,
            ]);

            $order->activities()->create([
                'event' => OrderEvent::RefundRequested->value,
                'previous_status' => $payment->status->value,
                'new_status' => $payment->status->value,
                'actor_id' => $admin->id,
                'actor_name' => $admin->name,
                'note' => 'Refund requested: '.$refund->amount_formatted.($reason ? ' — '.$reason : ''),
                'occurred_at' => now(),
            ]);

            return $refund;
        });

        try {
            $stripeRefund = $this->gateway->createRefund($payment, $amountCents, 'refund_'.$requestKey);

            $refund->update([
                'stripe_refund_id' => data_get($stripeRefund, 'id'),
                'status' => $this->mapStatus((string) data_get($stripeRefund, 'status')),
            ]);

            if ($refund->status === RefundStatus::Succeeded) {
                $this->reconciler->applyRefund($refund, $stripeRefund);
            }
        } catch (Throwable $exception) {
            $refund->update([
                'status' => RefundStatus::Failed,
                'failure_reason' => Str::limit($exception->getMessage(), 1000),
            ]);

            report($exception);

            throw ValidationException::withMessages([
                'refund' => 'Stripe could not create the refund. Please verify the payment status and try again.',
            ]);
        }

        return $refund->fresh();
    }

    private function mapStatus(string $status): RefundStatus
    {
        return match ($status) {
            'succeeded' => RefundStatus::Succeeded,
            'failed' => RefundStatus::Failed,
            'canceled' => RefundStatus::Canceled,
            default => RefundStatus::Pending,
        };
    }
}
