<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'provider',
        'payment_intent_id',
        'stripe_customer_id',
        'amount_cents',
        'currency',
        'status',
        'error_code',
        'error_message',
        'paid_at',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function getRefundedCentsAttribute(): int
    {
        return (int) $this->refunds()
            ->where('status', RefundStatus::Succeeded->value)
            ->sum('amount_cents');
    }

    public function getRefundableCentsAttribute(): int
    {
        $reserved = (int) $this->refunds()
            ->whereIn('status', [RefundStatus::Pending->value, RefundStatus::Succeeded->value])
            ->sum('amount_cents');

        return max(0, $this->amount_cents - $reserved);
    }

    public function getAmountFormattedAttribute(): string
    {
        return '$'.number_format($this->amount_cents / 100, 2);
    }
}
