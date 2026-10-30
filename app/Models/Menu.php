<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'location',
    ];

    public function getRouteKeyName(): string
    {
        return 'location';
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function scopeForLocation($query, string $location)
    {
        return $query->where('location', $location);
    }
}
