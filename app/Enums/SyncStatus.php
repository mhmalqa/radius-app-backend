<?php

namespace App\Enums;

enum SyncStatus: int
{
    case SUCCESS = 0;
    case FAILED = 1;
    case PENDING = 2;

    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'نجح',
            self::FAILED => 'فشل',
            self::PENDING => 'معلق',
        };
    }
}

