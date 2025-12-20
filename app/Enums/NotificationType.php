<?php

namespace App\Enums;

enum NotificationType: string
{
    case SYSTEM = 'system';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::SYSTEM => 'نظام',
            self::MANUAL => 'يدوي',
        };
    }
}

