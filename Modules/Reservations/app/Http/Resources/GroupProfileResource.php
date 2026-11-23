<?php

namespace Modules\Reservations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Models\GroupProfile;

/** @mixin GroupProfile */
class GroupProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'description' => $this->description,
            'contact_person_name' => $this->contact_person_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'logo_url' => $this->logo?->url,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
