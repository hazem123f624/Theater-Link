<?php

namespace Modules\Tenancy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Enums\GroupDocumentStatus;
use Modules\Tenancy\Models\GroupApplication;
use Modules\Tenancy\Services\GroupApplicationReviewService;

/**
 * @mixin GroupApplication
 */
class AdminGroupApplicationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tenant = $this->tenant;
        $owner = $tenant->members->firstWhere('is_owner', true);
        $documents = $this->relationLoaded('documents') ? $this->documents : collect();
        $profile = $tenant->groupProfile;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'previous_status' => $this->previous_status,
            'submitted_at' => $this->submitted_at,
            'last_action_at' => $this->last_action_at,
            'approved_at' => $this->approved_at,
            'reviewed_at' => $this->reviewed_at,
            'rejection_reason' => $this->rejection_reason,
            'review_feedback' => $this->review_feedback,
            'admin_notes' => $this->admin_notes,
            'suspended_at' => $this->suspended_at,
            'suspension_reason' => $this->suspension_reason,
            'group' => [
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'type' => $tenant->type,
                'status' => $tenant->status,
                'description' => $profile?->description,
                'contact_person_name' => $profile?->contact_person_name,
                'email' => $profile?->email,
                'phone' => $profile?->phone,
            ],
            'owner' => $owner ? [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'phone' => $owner->phone,
                'phone_verified_at' => $owner->phone_verified_at,
            ] : null,
            'documents' => AdminGroupDocumentResource::collection($documents),
            'documents_count' => $documents->count(),
            'documents_accepted' => $documents->where('status', GroupDocumentStatus::ACCEPTED->value)->count(),
            'documents_pending' => $documents->where('status', GroupDocumentStatus::PENDING->value)->count(),
            'history' => GroupApplicationHistoryResource::collection(
                $this->relationLoaded('histories') ? $this->histories : collect()
            ),
            'approval_blockers' => app(GroupApplicationReviewService::class)->approvalBlockers($this->resource),
            'can_approve' => $this->status !== 'approved'
                && $this->status !== 'rejected'
                && app(GroupApplicationReviewService::class)->approvalBlockers($this->resource) === [],
        ];
    }
}
