<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    public static function getValue(string $group, string $key): ?string
    {
        $setting = static::where('group', $group)->where('key', $key)->first();

        return $setting?->value;
    }

    public static function setValue(string $group, string $key, ?string $value): static
    {
        return static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value]
        );
    }
}
