<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'checkout_token',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'currency',
        'subtotal_cents',
        'fulfillment_fee_cents',
        'tax_cents',
        'total_cents',
        'fulfillment_method',
        'order_status',
        'payment_status',
        'fulfillment_status',
        'billing_address_snapshot',
        'fulfillment_address_snapshot',
        'pickup_location_snapshot',
        'fulfillment_details_snapshot',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'fulfillment_fee_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'fulfillment_method' => FulfillmentMethod::class,
            'order_status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'billing_address_snapshot' => 'array',
            'fulfillment_address_snapshot' => 'array',
            'pickup_location_snapshot' => 'array',
            'fulfillment_details_snapshot' => 'array',
            'placed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function getTotalFormattedAttribute(): string
    {
        return '$'.number_format($this->total_cents / 100, 2);
    }
}
