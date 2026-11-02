<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeletedTestOrderPaymentIntent extends Model
{
    protected $fillable = [
        'deleted_test_order_id',
        'payment_intent_id',
    ];

    public function deletedTestOrder(): BelongsTo
    {
        return $this->belongsTo(DeletedTestOrder::class);
    }
}
