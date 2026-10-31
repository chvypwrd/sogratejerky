<?php

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case ReadyForPickup = 'ready_for_pickup';
    case PickedUp = 'picked_up';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Shipped = 'shipped';

    public function label(): string
    {
        return match ($this) {
            self::Unfulfilled => 'Unfulfilled',
            self::ReadyForPickup => 'Ready for Pickup',
            self::PickedUp => 'Picked Up',
            self::OutForDelivery => 'Out for Delivery',
            self::Delivered => 'Delivered',
            self::Shipped => 'Shipped',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unfulfilled => 'warning',
            self::ReadyForPickup => 'info',
            self::PickedUp => 'success',
            self::OutForDelivery => 'info',
            self::Delivered => 'success',
            self::Shipped => 'success',
        };
    }
}
