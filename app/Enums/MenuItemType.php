<?php

namespace App\Enums;

enum MenuItemType: string
{
    case Page = 'page';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Page => 'Page',
            self::Custom => 'Custom URL',
        };
    }
}
