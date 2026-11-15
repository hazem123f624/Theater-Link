<?php

namespace App\Enums;

use App\Traits\HasTranslation;

enum ActiveStatus: int
{
    use HasTranslation;

    case ACTIVE = 1;
    case INACTIVE = 0;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function translationPrefix(): string
    {
        return 'manageplans';
    }
}
