<?php

namespace App\Enums;

enum MenuTarget: string
{
    case Self = '_self';
    case Blank = '_blank';

    public function label(): string
    {
        return match ($this) {
            self::Self => 'Same Window',
            self::Blank => 'New Window',
        };
    }
}
