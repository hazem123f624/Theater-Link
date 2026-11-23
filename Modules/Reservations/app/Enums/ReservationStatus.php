<?php

namespace Modules\Reservations\Enums;

enum ReservationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case COMPLETED = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
