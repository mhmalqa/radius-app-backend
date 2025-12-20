<?php

namespace App\Enums;

enum UserRole: int
{
    case USER = 0;
    case ACCOUNTANT = 1;
    case ADMIN = 2;

    public function label(): string
    {
        return match ($this) {
            self::USER => 'مستخدم',
            self::ACCOUNTANT => 'محاسب',
            self::ADMIN => 'مدير',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::ACCOUNTANT => 'Accountant',
            self::ADMIN => 'Admin',
        };
    }
}

