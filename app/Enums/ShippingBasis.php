<?php

namespace App\Enums;

enum ShippingBasis: string
{
    case Subtotal = 'subtotal';
    case Quantity = 'quantity';

    public function label(): string
    {
        return match ($this) {
            self::Subtotal => 'Order Subtotal',
            self::Quantity => 'Total Item Quantity',
        };
    }
}
