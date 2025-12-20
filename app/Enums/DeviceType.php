<?php

namespace App\Enums;

enum DeviceType: string
{
    case IOS = 'ios';
    case ANDROID = 'android';
    case WEB = 'web';

    public function label(): string
    {
        return match ($this) {
            self::IOS => 'iOS',
            self::ANDROID => 'Android',
            self::WEB => 'Web',
        };
    }
}

