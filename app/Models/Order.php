<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShippingCarrier;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number',
        'checkout_token',
        'cart_id',
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
        'shipping_carrier',
        'tracking_number',
        'ready_for_pickup_at',
        'picked_up_at',
        'out_for_delivery_at',
        'delivered_at',
        'shipped_at',
        'fulfilled_at',
        'cancelled_at',
        'cancellation_reason',
        'placed_at',
        'ga4_purchase_tracked_at',
        'is_test_order',
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
            'shipping_carrier' => ShippingCarrier::class,
            'billing_address_snapshot' => 'array',
            'fulfillment_address_snapshot' => 'array',
            'pickup_location_snapshot' => 'array',
            'fulfillment_details_snapshot' => 'array',
            'ready_for_pickup_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'shipped_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'placed_at' => 'datetime',
            'ga4_purchase_tracked_at' => 'datetime',
            'is_test_order' => 'boolean',
        ];
    }

    public function scopeRegular(Builder $query): Builder
    {
        return $query->where('is_test_order', false);
    }

    public function scopeTestOrders(Builder $query): Builder
    {
        return $query->where('is_test_order', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OrderActivity::class)->latest('occurred_at')->latest('id');
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function getTotalFormattedAttribute(): string
    {
        return '$'.number_format($this->total_cents / 100, 2);
    }

    public function getTrackingUrlAttribute(): ?string
    {
        return $this->shipping_carrier?->trackingUrl($this->tracking_number);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isCancelled(): bool
    {
        return $this->order_status === OrderStatus::Cancelled;
    }

    public function isTerminalCheckout(): bool
    {
        return $this->isCancelled() || in_array($this->payment_status, [
            PaymentStatus::Paid,
            PaymentStatus::PartiallyRefunded,
            PaymentStatus::Refunded,
        ], true);
    }

    public function belongsToCheckoutContext(Cart $cart, ?User $user): bool
    {
        if ((int) $this->cart_id !== (int) $cart->id) {
            return false;
        }

        if ($this->user_id === null) {
            return $user === null;
        }

        return $user !== null && (int) $this->user_id === (int) $user->id;
    }

    public function isFulfillmentComplete(): bool
    {
        return $this->fulfilled_at !== null;
    }
}
