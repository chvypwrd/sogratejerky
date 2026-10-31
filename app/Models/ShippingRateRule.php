<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShippingRateRule extends Model
{
    protected $fillable = [
        'min_value',
        'max_value',
        'fee_cents',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_value' => 'integer',
            'max_value' => 'integer',
            'fee_cents' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('min_value')->orderBy('sort_order');
    }
}
