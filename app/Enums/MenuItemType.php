<?php

namespace App\Enums;

enum MenuItemType: string
{
    case Page = 'page';
    case Class = 'class';
    case Instructor = 'instructor';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Page => 'Page',
            self::class => 'Class',
            self::Instructor => 'Instructor',
            self::Custom => 'Custom URL',
        };
    }
}
