<?php

namespace App\Models;

use App\Enums\OrderEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderActivity extends Model
{
    protected $fillable = [
        'order_id',
        'event',
        'previous_status',
        'new_status',
        'actor_id',
        'actor_name',
        'note',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event' => OrderEvent::class,
            'occurred_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
