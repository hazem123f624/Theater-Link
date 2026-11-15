<?php

namespace Modules\Tenancy\Enums;

enum TenantStatus: string
{
    case PENDING_REVIEW = 'pending_review';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REJECTED = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canUseWorkspace(): bool
    {
        return $this === self::ACTIVE;
    }
}
