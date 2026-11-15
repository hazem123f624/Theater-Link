<?php

namespace Modules\Tenancy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Models\TenantInvitation;

/**
 * @mixin TenantInvitation
 */
class TenantInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'role_id' => $this->role_id,
            'expires_at' => $this->expires_at,
            'accepted_at' => $this->accepted_at,
            'token' => config('app.env') !== 'production' ? $this->token : null,
        ];
    }
}
