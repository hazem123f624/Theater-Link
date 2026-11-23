<?php

namespace Modules\Bookings\Enums;

enum TicketStatus: string
{
    case VALID = 'valid';
    case USED = 'used';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
