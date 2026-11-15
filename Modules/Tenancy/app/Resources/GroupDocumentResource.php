<?php

namespace Modules\Tenancy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Models\GroupApplicationDocument;

/**
 * @mixin GroupApplicationDocument
 */
class GroupDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->document_type,
            'name' => $this->original_name,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'admin_note' => $this->admin_note,
        ];
    }
}
