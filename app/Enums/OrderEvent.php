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
        };
    }
}
