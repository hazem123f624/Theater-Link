<?php

namespace Modules\Tenancy\Enums;

enum GroupDocumentStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case REQUIRES_RESUBMISSION = 'requires_resubmission';
}
