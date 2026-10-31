<?php

namespace App\Enums;

enum FulfillmentMethod: string
{
    case Shipping = 'shipping';
    case LocalDelivery = 'local_delivery';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Shipping => 'Shipping',
            self::LocalDelivery => 'Local Delivery',
            self::Pickup => 'Pickup',
        };
    }
}
