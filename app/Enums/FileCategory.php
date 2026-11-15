<?php

namespace App\Enums;

use App\Traits\HasTranslation;

enum FileCategory: string
{
    use HasTranslation;

    case VALUATION_REPORT       = 'valuation_report';
    case REGISTRATION_DOCUMENT  = 'registration_document';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function translationPrefix(): string
    {
        return 'manageplans';
    }
}
