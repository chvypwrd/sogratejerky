<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Placed = 'placed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::Placed => 'Placed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Placed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
