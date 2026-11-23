<?php

namespace Modules\Bookings\Enums;

enum BookingStatus: string
{
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
