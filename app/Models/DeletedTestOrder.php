<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeletedTestOrder extends Model
{
    protected $fillable = [
        'original_order_id',
        'order_number',
        'original_total_cents',
        'original_placed_at',
        'verification_basis',
        'record_counts',
        'deleted_by',
        'deleted_by_name',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'original_total_cents' => 'integer',
            'original_placed_at' => 'datetime',
            'record_counts' => 'array',
            'deleted_at' => 'datetime',
        ];
    }

    public function paymentIntents(): HasMany
    {
        return $this->hasMany(DeletedTestOrderPaymentIntent::class);
    }
}
