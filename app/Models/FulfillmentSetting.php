<?php

namespace App\Models;

use App\Enums\ShippingBasis;
use Illuminate\Database\Eloquent\Model;

class FulfillmentSetting extends Model
{
    protected $fillable = [
        'shipping_enabled',
        'local_delivery_enabled',
        'pickup_enabled',
        'shipping_basis',
        'local_delivery_threshold_enabled',
        'local_delivery_threshold_cents',
        'local_delivery_flat_fee_cents',
    ];

    protected function casts(): array
    {
        return [
            'shipping_enabled' => 'boolean',
            'local_delivery_enabled' => 'boolean',
            'pickup_enabled' => 'boolean',
            'shipping_basis' => ShippingBasis::class,
            'local_delivery_threshold_enabled' => 'boolean',
            'local_delivery_threshold_cents' => 'integer',
            'local_delivery_flat_fee_cents' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'shipping_enabled' => true,
            'local_delivery_enabled' => false,
            'pickup_enabled' => false,
            'shipping_basis' => ShippingBasis::Subtotal,
            'local_delivery_threshold_enabled' => false,
            'local_delivery_threshold_cents' => 0,
            'local_delivery_flat_fee_cents' => 0,
        ]);
    }
}
