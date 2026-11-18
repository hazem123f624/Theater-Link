<?php

namespace Modules\Tenancy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Enums\GroupDocumentStatus;
use Modules\Tenancy\Models\GroupApplication;

/**
 * @mixin GroupApplication
 */
class AdminGroupApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tenant = $this->tenant;
        $owner = $tenant->members->firstWhere('is_owner', true);
        $documents = $this->relationLoaded('documents') ? $this->documents : collect();

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'previous_status' => $this->previous_status,
            'submitted_at' => $this->submitted_at,
            'last_action_at' => $this->last_action_at,
            'approved_at' => $this->approved_at,
            'suspended_at' => $this->suspended_at,
            'suspension_reason' => $this->suspension_reason,
            'group_name' => $tenant->name,
            'subdomain' => $tenant->subdomain,
            'tenant_status' => $tenant->status,
            'owner' => $owner ? [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'phone' => $owner->phone,
                'phone_verified_at' => $owner->phone_verified_at,
            ] : null,
            'documents_count' => $documents->count(),
            'documents_accepted' => $documents->where('status', GroupDocumentStatus::ACCEPTED->value)->count(),
            'documents_pending' => $documents->where('status', GroupDocumentStatus::PENDING->value)->count(),
        ];
    }
}
