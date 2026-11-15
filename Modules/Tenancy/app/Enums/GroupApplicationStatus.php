<?php

namespace Modules\Tenancy\Enums;

enum GroupApplicationStatus: string
{
    case PENDING_REVIEW = 'pending_review';
    case UNDER_REVIEW = 'under_review';
    case REQUIRES_RESUBMISSION = 'requires_resubmission';
    case READY_FOR_REVIEW = 'ready_for_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
