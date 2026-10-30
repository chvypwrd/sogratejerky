<?php

namespace App\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';
    case Archived = 'archived';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Read => 'Read',
            self::Replied => 'Replied',
            self::Archived => 'Archived',
            self::Spam => 'Spam',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'primary',
            self::Read => 'info',
            self::Replied => 'success',
            self::Archived => 'secondary',
            self::Spam => 'danger',
        };
    }
}
