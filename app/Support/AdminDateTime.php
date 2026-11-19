<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;

class AdminDateTime
{
    public const PLACEHOLDER = '—';

    public static function format(?DateTimeInterface $value, string $format = 'M j, Y g:i A'): string
    {
        if (! $value) {
            return self::PLACEHOLDER;
        }

        return Carbon::instance($value)
            ->setTimezone((string) config('app.admin_timezone', 'America/Chicago'))
            ->format($format);
    }

    public static function formatDate(?DateTimeInterface $value): string
    {
        return self::format($value, 'M j, Y');
    }
}
