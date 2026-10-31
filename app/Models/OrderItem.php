<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'sku',
        'quantity',
        'unit_price_cents',
        'line_total_cents',
        'weight_grams',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'line_total_cents' => 'integer',
            'weight_grams' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getUnitPriceFormattedAttribute(): string
    {
        return '$'.number_format($this->unit_price_cents / 100, 2);
    }

    public function getLineTotalFormattedAttribute(): string
    {
        return '$'.number_format($this->line_total_cents / 100, 2);
    }
}
