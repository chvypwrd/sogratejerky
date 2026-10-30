<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = [
        'old_path',
        'new_path',
        'status_code',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'active' => 'boolean',
        ];
    }

    public static function findForPath(string $path): ?self
    {
        return static::where('old_path', '/'.ltrim($path, '/'))
            ->where('active', true)
            ->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
