<?php

namespace App\Enums;

enum NotificationPriority: int
{
    case NORMAL = 0;
    case IMPORTANT = 1;
    case URGENT = 2;

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'عادي',
            self::IMPORTANT => 'مهم',
            self::URGENT => 'عاجل',
        };
    }
}

