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
class GroupApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $documents = $this->relationLoaded('documents') ? $this->documents : collect();
        $needsReupload = $documents->whereIn('status', [
            GroupDocumentStatus::REJECTED->value,
            GroupDocumentStatus::REQUIRES_RESUBMISSION->value,
        ])->count();
        $blockers = app(GroupApplicationReviewService::class)->approvalBlockers($this->resource);
        $owner = $this->tenant->members->firstWhere('is_owner', true);

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'previous_status' => $this->previous_status,
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'last_action_at' => $this->last_action_at,
            'rejection_reason' => $this->rejection_reason,
            'review_feedback' => $this->review_feedback,
            'owner_phone_verified' => (bool) $owner?->phone_verified_at,
            'required_actions' => array_values(array_unique(array_merge(
                $blockers,
                $needsReupload > 0 ? ["{$needsReupload} document(s) need to be uploaded again"] : [],
            ))),
            'documents' => GroupDocumentResource::collection($documents),
            'documents_count' => $documents->count(),
            'documents_accepted' => $documents->where('status', GroupDocumentStatus::ACCEPTED->value)->count(),
            'history' => GroupApplicationHistoryResource::collection(
                $this->relationLoaded('histories') ? $this->histories : collect()
            ),
            'support' => [
                'email' => config('tenancy.support.email'),
                'phone' => config('tenancy.support.phone'),
            ],
        ];
    }
}
