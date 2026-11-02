<?php

namespace App\Services;

use App\Enums\OrderEvent;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\DeletedTestOrderPaymentIntent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\StripeEvent;
use App\Notifications\AdminPaidOrderNotification;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PaymentReconciler
{
    public function __construct(
        private StripeGateway $gateway,
        private CartService $cartService,
        private SettingsService $settingsService,
    ) {}

    public function reconcilePayment(Payment $payment): Payment
    {
        if (! $payment->payment_intent_id) {
            return $payment;
        }

        $intent = $this->gateway->retrievePaymentIntent($payment->payment_intent_id);

        $this->applyPaymentIntent($payment, $intent);

        return $payment->fresh();
    }

    public function handleEvent(object $event): void
    {
        $eventId = (string) data_get($event, 'id');
        $eventType = (string) data_get($event, 'type');

        if ($eventId === '' || $eventType === '') {
            throw new RuntimeException('Invalid Stripe event payload.');
        }

        $receipt = StripeEvent::firstOrCreate(
            ['stripe_event_id' => $eventId],
            ['type' => $eventType, 'status' => 'processing'],
        );

        if (! $receipt->wasRecentlyCreated && $receipt->status === 'processed') {
            return;
        }

        try {
            if ($this->isDeletedTestOrderEvent($event)) {
                $receipt->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'failure_message' => null,
                ]);

                return;
            }

            if (str_starts_with($eventType, 'payment_intent.')) {
                $this->handlePaymentIntentEvent($event);
            } elseif (str_starts_with($eventType, 'refund.')) {
                $this->handleRefundEvent($event);
            }

            $receipt->update([
                'status' => 'processed',
                'processed_at' => now(),
                'failure_message' => null,
            ]);
        } catch (Throwable $exception) {
            $receipt->update([
                'status' => 'failed',
                'failure_message' => Str::limit($exception->getMessage(), 1000),
            ]);

            throw $exception;
        }
    }

    public function applyPaymentIntent(Payment $payment, object $intent): void
    {
        [$paidOrder, $shouldNotify] = DB::transaction(function () use ($payment, $intent) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            $this->validateIntent($payment, $intent);

            $intentStatus = (string) data_get($intent, 'status', '');
            $previous = $payment->status;

            if ($intentStatus === 'succeeded') {
                if (in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded, PaymentStatus::Refunded], true)) {
                    return [null, false];
                }

                $payment->update([
                    'status' => PaymentStatus::Paid,
                    'stripe_livemode' => data_get($intent, 'livemode'),
                    'paid_at' => now(),
                    'error_code' => null,
                    'error_message' => null,
                ]);

                if ($order->isCancelled()) {
                    $order->update(['payment_status' => PaymentStatus::Paid]);
                    $this->recordActivity($order, OrderEvent::PaymentPaid, $previous->value, PaymentStatus::Paid->value, 'Stripe payment confirmed after cancellation.');

                    return [null, false];
                }

                $order->update([
                    'payment_status' => PaymentStatus::Paid,
                    'order_status' => OrderStatus::Placed,
                ]);

                $this->recordActivity($order, OrderEvent::PaymentPaid, $previous->value, PaymentStatus::Paid->value, 'Stripe payment confirmed.');

                return [$order->fresh(), true];
            }

            if (in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::PartiallyRefunded, PaymentStatus::Refunded], true)) {
                return [null, false];
            }

            if (in_array($intentStatus, ['processing', 'requires_action', 'requires_confirmation'], true)) {
                $payment->update([
                    'status' => PaymentStatus::Processing,
                    'stripe_livemode' => data_get($intent, 'livemode'),
                    'error_code' => null,
                    'error_message' => null,
                ]);
                $order->update(['payment_status' => PaymentStatus::Processing]);

                if ($previous !== PaymentStatus::Processing) {
                    $this->recordActivity($order, OrderEvent::PaymentProcessing, $previous->value, PaymentStatus::Processing->value, 'Stripe payment is processing.');
                }

                return [null, false];
            }

            if (in_array($intentStatus, ['requires_payment_method', 'canceled'], true)) {
                $errorCode = data_get($intent, 'last_payment_error.code');
                $errorMessage = data_get($intent, 'last_payment_error.message');

                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'stripe_livemode' => data_get($intent, 'livemode'),
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage ?: ($intentStatus === 'canceled' ? 'Payment intent canceled.' : 'Payment failed.'),
                    'canceled_at' => $intentStatus === 'canceled' ? now() : $payment->canceled_at,
                ]);
                $order->update(['payment_status' => PaymentStatus::Failed]);

                if ($previous !== PaymentStatus::Failed) {
                    $this->recordActivity($order, OrderEvent::PaymentFailed, $previous->value, PaymentStatus::Failed->value, $payment->error_message);
                }

                return [null, false];
            }

            return [null, false];
        });

        if ($paidOrder) {
            $this->cartService->consumeForOrder($paidOrder);

            if ($shouldNotify) {
                try {
                    Notification::route('mail', $paidOrder->customer_email)
                        ->notify(new OrderStatusNotification($paidOrder, OrderEvent::PaymentPaid));
                } catch (Throwable $exception) {
                    report($exception);
                }

                $this->notifyAdminOfPaidOrder($paidOrder);
            }
        }
    }

    private function notifyAdminOfPaidOrder(Order $order): void
    {
        $notificationEmail = trim((string) $this->settingsService->get('contact.notification_email', ''));

        if ($notificationEmail === '' || filter_var($notificationEmail, FILTER_VALIDATE_EMAIL) === false) {
            Log::warning('Administrative paid-order notification skipped because contact.notification_email is empty or invalid.', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            return;
        }

        try {
            Notification::route('mail', $notificationEmail)
                ->notify(new AdminPaidOrderNotification($order));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function applyRefund(PaymentRefund $refund, object $stripeRefund): void
    {
        DB::transaction(function () use ($refund, $stripeRefund) {
            $refund = PaymentRefund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->whereKey($refund->payment_id)->lockForUpdate()->firstOrFail();
            $order = Order::query()->whereKey($refund->order_id)->lockForUpdate()->firstOrFail();

            $intentId = data_get($stripeRefund, 'payment_intent');
            $intentId = is_object($intentId) ? $intentId->id : $intentId;

            if ($intentId !== $payment->payment_intent_id) {
                throw new RuntimeException('Refund does not match the original payment intent.');
            }

            if ((int) data_get($stripeRefund, 'amount') !== $refund->amount_cents) {
                throw new RuntimeException('Refund amount does not match the recorded request.');
            }

            if (strtoupper((string) data_get($stripeRefund, 'currency')) !== strtoupper($refund->currency)) {
                throw new RuntimeException('Refund currency does not match the recorded payment.');
            }

            $status = $this->mapRefundStatus((string) data_get($stripeRefund, 'status'));
            $previousRefundStatus = $refund->status;

            if ($previousRefundStatus === RefundStatus::Succeeded && $status !== RefundStatus::Succeeded) {
                return;
            }

            $refund->update([
                'stripe_refund_id' => data_get($stripeRefund, 'id'),
                'status' => $status,
                'failure_reason' => $status === RefundStatus::Failed ? data_get($stripeRefund, 'failure_reason') : null,
                'refunded_at' => $status === RefundStatus::Succeeded ? ($refund->refunded_at ?? now()) : $refund->refunded_at,
            ]);

            if ($status === RefundStatus::Succeeded) {
                $previousPaymentStatus = $payment->status;
                $this->syncRefundTotals($payment, $order);
                $currentPaymentStatus = $order->fresh()->payment_status;

                if ($previousRefundStatus !== RefundStatus::Succeeded || $currentPaymentStatus !== $previousPaymentStatus) {
                    $this->recordActivity(
                        $order,
                        OrderEvent::RefundCompleted,
                        $previousPaymentStatus->value,
                        $currentPaymentStatus->value,
                        'Refunded '.$refund->amount_formatted.' via Stripe.',
                    );
                }
            }
        });
    }

    private function isDeletedTestOrderEvent(object $event): bool
    {
        $object = data_get($event, 'data.object');

        if (! is_object($object)) {
            return false;
        }

        $paymentIntentIds = [];
        $paymentIntentId = data_get($object, 'payment_intent');

        if (is_object($paymentIntentId)) {
            $paymentIntentId = $paymentIntentId->id ?? null;
        }

        if (is_string($paymentIntentId) && $paymentIntentId !== '') {
            $paymentIntentIds[] = $paymentIntentId;
        }

        $eventType = (string) data_get($event, 'type');

        if (str_starts_with($eventType, 'payment_intent.')) {
            $objectId = data_get($object, 'id');

            if (is_string($objectId) && $objectId !== '') {
                $paymentIntentIds[] = $objectId;
            }
        }

        if ($paymentIntentIds === []) {
            return false;
        }

        return DeletedTestOrderPaymentIntent::query()
            ->whereIn('payment_intent_id', array_values(array_unique($paymentIntentIds)))
            ->exists();
    }

    private function handlePaymentIntentEvent(object $event): void
    {
        $intent = data_get($event, 'data.object');

        if (! is_object($intent)) {
            throw new RuntimeException('Stripe payment intent payload is missing.');
        }

        $payment = Payment::where('payment_intent_id', data_get($intent, 'id'))->first();

        if (! $payment) {
            throw new RuntimeException('Payment record not found for Stripe payment intent.');
        }

        $this->applyPaymentIntent($payment, $intent);
    }

    private function handleRefundEvent(object $event): void
    {
        $stripeRefund = data_get($event, 'data.object');

        if (! is_object($stripeRefund)) {
            throw new RuntimeException('Stripe refund payload is missing.');
        }

        $refund = PaymentRefund::where('stripe_refund_id', data_get($stripeRefund, 'id'))->first();

        if (! $refund) {
            throw new RuntimeException('Refund record not found for Stripe refund.');
        }

        $this->applyRefund($refund, $stripeRefund);
    }

    private function validateIntent(Payment $payment, object $intent): void
    {
        if ((int) data_get($intent, 'amount') !== $payment->amount_cents) {
            throw new RuntimeException('Stripe payment amount does not match the order total.');
        }

        if (strtoupper((string) data_get($intent, 'currency')) !== strtoupper($payment->currency)) {
            throw new RuntimeException('Stripe payment currency does not match the order currency.');
        }

        $metadataOrderId = data_get($intent, 'metadata.order_id');

        if ($metadataOrderId !== null && (string) $metadataOrderId !== (string) $payment->order_id) {
            throw new RuntimeException('Stripe payment metadata does not match the local order.');
        }
    }

    private function syncRefundTotals(Payment $payment, Order $order): void
    {
        $refunded = (int) $payment->refunds()
            ->where('status', RefundStatus::Succeeded->value)
            ->sum('amount_cents');

        $newStatus = $refunded >= $payment->amount_cents
            ? PaymentStatus::Refunded
            : ($refunded > 0 ? PaymentStatus::PartiallyRefunded : PaymentStatus::Paid);

        if ($payment->status !== $newStatus) {
            $payment->update(['status' => $newStatus]);
        }

        if ($order->payment_status !== $newStatus) {
            $order->update(['payment_status' => $newStatus]);
        }
    }

    private function mapRefundStatus(string $status): RefundStatus
    {
        return match ($status) {
            'succeeded' => RefundStatus::Succeeded,
            'failed' => RefundStatus::Failed,
            'canceled' => RefundStatus::Canceled,
            default => RefundStatus::Pending,
        };
    }

    private function recordActivity(
        Order $order,
        OrderEvent $event,
        ?string $previousStatus,
        ?string $newStatus,
        ?string $note,
    ): void {
        $order->activities()->create([
            'event' => $event->value,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'actor_id' => null,
            'actor_name' => 'Stripe',
            'note' => $note,
            'occurred_at' => now(),
        ]);
    }
}
