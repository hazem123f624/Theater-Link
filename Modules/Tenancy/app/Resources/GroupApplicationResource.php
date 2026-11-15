<?php

namespace Modules\Tenancy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Enums\GroupDocumentStatus;
use Modules\Tenancy\Models\GroupApplication;

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

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'approved_at' => $this->approved_at,
            'rejection_reason' => $this->rejection_reason,
            'review_feedback' => $this->review_feedback,
            'required_actions' => $needsReupload > 0
                ? ["{$needsReupload} document(s) need to be uploaded again"]
                : [],
            'documents' => GroupDocumentResource::collection($documents),
            'documents_count' => $documents->count(),
            'documents_accepted' => $documents->where('status', GroupDocumentStatus::ACCEPTED->value)->count(),
            'support' => [
                'email' => config('tenancy.support.email'),
                'phone' => config('tenancy.support.phone'),
            ],
        ];
    }
}
