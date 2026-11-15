<?php

namespace Modules\Tenancy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Models\TenantMember;

/**
 * @mixin TenantMember
 */
class TenantTeamMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var TenantMember $member */
        $member = $this->resource;

        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'phone' => $member->phone,
            'is_owner' => (bool) $member->is_owner,
            'is_active' => (bool) $member->is_active,
            'roles' => $this->whenLoaded('roles', fn () => $member->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'template_key' => $role->template_key,
            ])->values()),
            'last_login_at' => $member->last_login_at,
            'created_at' => $member->created_at,
        ];
    }
}
