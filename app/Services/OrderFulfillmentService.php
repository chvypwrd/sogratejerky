<?php

namespace App\Services;

use App\Enums\FulfillmentMethod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderEvent;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingCarrier;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class OrderFulfillmentService
{
    public function markReadyForPickup(Order $order, User $actor, ?string $note = null): Order
    {
        [$order, $changed] = $this->transition(
            $order,
            $actor,
            FulfillmentMethod::Pickup,
            FulfillmentStatus::Unfulfilled,
            FulfillmentStatus::ReadyForPickup,
            OrderEvent::ReadyForPickup,
            ['ready_for_pickup_at' => now()],
            $note,
        );

        if ($changed) {
            $this->notify($order, OrderEvent::ReadyForPickup);
        }

        return $order;
    }

    public function markPickedUp(Order $order, User $actor, ?string $note = null): Order
    {
        [$order, $changed] = $this->transition(
            $order,
            $actor,
            FulfillmentMethod::Pickup,
            FulfillmentStatus::ReadyForPickup,
            FulfillmentStatus::PickedUp,
            OrderEvent::PickedUp,
            ['picked_up_at' => now(), 'fulfilled_at' => now()],
            $note,
        );

        if ($changed) {
            $this->notify($order, OrderEvent::PickedUp);
        }

        return $order;
    }

    public function markOutForDelivery(Order $order, User $actor, ?string $note = null): Order
    {
        [$order, $changed] = $this->transition(
            $order,
            $actor,
            FulfillmentMethod::LocalDelivery,
            FulfillmentStatus::Unfulfilled,
            FulfillmentStatus::OutForDelivery,
            OrderEvent::OutForDelivery,
            ['out_for_delivery_at' => now()],
            $note,
        );

        if ($changed) {
            $this->notify($order, OrderEvent::OutForDelivery);
        }

        return $order;
    }

    public function markDelivered(Order $order, User $actor, ?string $note = null): Order
    {
        [$order, $changed] = $this->transition(
            $order,
            $actor,
            FulfillmentMethod::LocalDelivery,
            FulfillmentStatus::OutForDelivery,
            FulfillmentStatus::Delivered,
            OrderEvent::Delivered,
            ['delivered_at' => now(), 'fulfilled_at' => now()],
            $note,
        );

        if ($changed) {
            $this->notify($order, OrderEvent::Delivered);
        }

        return $order;
    }

    public function markShipped(
        Order $order,
        User $actor,
        ShippingCarrier $carrier,
        ?string $trackingNumber,
        bool $untracked = false,
        ?string $note = null,
    ): Order {
        $trackingNumber = $untracked ? null : trim((string) $trackingNumber);

        if (! $untracked && $trackingNumber === '') {
            throw ValidationException::withMessages([
                'tracking_number' => 'Enter a tracking number or confirm the shipment is untracked.',
            ]);
        }

        $activityNote = trim(implode(' ', array_filter([
            $note,
            'Carrier: '.$carrier->label().'.',
            $trackingNumber ? 'Tracking: '.$trackingNumber.'.' : 'Untracked shipment.',
        ])));

        [$order, $changed] = $this->transition(
            $order,
            $actor,
            FulfillmentMethod::Shipping,
            FulfillmentStatus::Unfulfilled,
            FulfillmentStatus::Shipped,
            OrderEvent::Shipped,
            [
                'shipping_carrier' => $carrier->value,
                'tracking_number' => $trackingNumber,
                'shipped_at' => now(),
                'fulfilled_at' => now(),
            ],
            $activityNote,
        );

        if ($changed) {
            $this->notify($order, OrderEvent::Shipped);
        }

        return $order;
    }

    public function cancel(Order $order, User $actor, ?string $reason = null): Order
    {
        [$order, $changed] = DB::transaction(function () use ($order, $actor, $reason) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->isCancelled()) {
                return [$order, false];
            }

            if ($order->isFulfillmentComplete()) {
                throw ValidationException::withMessages([
                    'order' => 'Completed orders cannot be cancelled.',
                ]);
            }

            if (! in_array($order->payment_status, [PaymentStatus::Pending, PaymentStatus::Failed, PaymentStatus::Refunded], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Only unpaid or fully refunded orders can be cancelled.',
                ]);
            }

            $previous = $order->order_status;
            $order->update([
                'order_status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->record(
                $order,
                $actor,
                OrderEvent::OrderCancelled,
                $previous->value,
                OrderStatus::Cancelled->value,
                $reason,
            );

            return [$order->fresh(), true];
        });

        if ($changed) {
            $this->notify($order, OrderEvent::OrderCancelled);
        }

        return $order;
    }

    private function transition(
        Order $order,
        User $actor,
        FulfillmentMethod $method,
        FulfillmentStatus $expected,
        FulfillmentStatus $target,
        OrderEvent $event,
        array $attributes,
        ?string $note,
    ): array {
        return DB::transaction(function () use ($order, $actor, $method, $expected, $target, $event, $attributes, $note) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->fulfillment_status === $target) {
                return [$order, false];
            }

            $this->assertFulfillable($order, $method);

            if ($order->fulfillment_status !== $expected) {
                throw ValidationException::withMessages([
                    'order' => 'This fulfillment action is not available for the current order status.',
                ]);
            }

            $previous = $order->fulfillment_status;
            $order->update($attributes + ['fulfillment_status' => $target]);

            $this->record($order, $actor, $event, $previous->value, $target->value, $note);

            return [$order->fresh(), true];
        });
    }

    private function assertFulfillable(Order $order, FulfillmentMethod $method): void
    {
        if ($order->isCancelled()) {
            throw ValidationException::withMessages([
                'order' => 'Cancelled orders cannot be fulfilled.',
            ]);
        }

        if (! $order->isPaid()) {
            throw ValidationException::withMessages([
                'order' => 'Payment must be verified as paid before fulfillment.',
            ]);
        }

        if ($order->fulfillment_method !== $method) {
            throw ValidationException::withMessages([
                'order' => 'This fulfillment action does not match the order fulfillment method.',
            ]);
        }
    }

    private function record(
        Order $order,
        User $actor,
        OrderEvent $event,
        ?string $previousStatus,
        ?string $newStatus,
        ?string $note,
    ): void {
        $order->activities()->create([
            'event' => $event->value,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'note' => $note,
            'occurred_at' => now(),
        ]);
    }

    private function notify(Order $order, OrderEvent $event): void
    {
        Notification::route('mail', $order->customer_email)
            ->notify(new OrderStatusNotification($order, $event));
    }
}
