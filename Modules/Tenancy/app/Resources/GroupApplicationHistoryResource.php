<?php

namespace Modules\Tenancy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Models\GroupApplicationStatusHistory;

/**
 * @mixin GroupApplicationStatusHistory
 */
class GroupApplicationHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_id' => $this->document_id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'action' => $this->action,
            'note' => $this->note,
            'actor' => optional($this->admin)->name ?? optional($this->member)->name,
            'actor_type' => $this->admin_id ? 'admin' : ($this->tenant_member_id ? 'member' : 'system'),
            'created_at' => $this->created_at,
        ];
    }
}
