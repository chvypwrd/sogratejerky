<?php

namespace App\Enums;

enum OrderEvent: string
{
    case OrderCreated = 'order_created';
    case OrderCancelled = 'order_cancelled';
    case ReadyForPickup = 'ready_for_pickup';
    case PickedUp = 'picked_up';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Shipped = 'shipped';
    case PaymentProcessing = 'payment_processing';
    case PaymentPaid = 'payment_paid';
    case PaymentFailed = 'payment_failed';
    case RefundRequested = 'refund_requested';
    case RefundCompleted = 'refund_completed';
    case TestOrderMarked = 'test_order_marked';
    case TestOrderUnmarked = 'test_order_unmarked';

    public function label(): string
    {
        return match ($this) {
            self::OrderCreated => 'Order created',
            self::OrderCancelled => 'Order cancelled',
            self::ReadyForPickup => 'Ready for pickup',
            self::PickedUp => 'Picked up',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Shipped => 'Shipped',
            self::PaymentProcessing => 'Payment processing',
            self::PaymentPaid => 'Payment confirmed',
            self::PaymentFailed => 'Payment failed',
            self::RefundRequested => 'Refund requested',
            self::RefundCompleted => 'Refund completed',
            self::TestOrderMarked => 'Marked as test order',
            self::TestOrderUnmarked => 'Test order status removed',
        };
    }
}
