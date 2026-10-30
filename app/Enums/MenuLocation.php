<?php

namespace App\Enums;

enum MenuLocation: string
{
    case Primary = 'primary';
    case Footer = 'footer';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Primary Navigation',
            self::Footer => 'Footer Navigation',
        };
    }
}
