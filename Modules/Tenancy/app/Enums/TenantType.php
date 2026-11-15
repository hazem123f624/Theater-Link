<?php

namespace Modules\Tenancy\Enums;

enum TenantType: string
{
    case THEATER = 'theater';
    case GROUP = 'group';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isTheater(): bool
    {
        return $this === self::THEATER;
    }

    public function isGroup(): bool
    {
        return $this === self::GROUP;
    }
}
