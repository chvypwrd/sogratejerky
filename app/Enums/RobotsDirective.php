<?php

namespace App\Enums;

enum RobotsDirective: string
{
    case IndexFollow = 'index,follow';
    case NoIndexFollow = 'noindex,follow';
    case NoIndexNoFollow = 'noindex,nofollow';

    public function label(): string
    {
        return match ($this) {
            self::IndexFollow => 'Index, Follow',
            self::NoIndexFollow => 'No Index, Follow',
            self::NoIndexNoFollow => 'No Index, No Follow',
        };
    }
}
