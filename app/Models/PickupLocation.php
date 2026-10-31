<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PickupLocation extends Model
{
    protected $fillable = [
        'name',
        'address',
        'contact_details',
        'instructions',
        'fee_cents',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
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
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
