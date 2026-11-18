<?php

namespace Modules\Tenancy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Models\TenantMember;

/**
 * @mixin TenantMember
 */
class TenantMemberResource extends JsonResource
{
    public ?string $token = null;

    public ?string $token_type = 'Bearer';

    public ?int $expires_in = null;

    public function toArray(Request $request): array
    {
        $tenant = $this->whenLoaded('tenant');
        $roles = $this->whenLoaded('roles');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_verified_at' => $this->phone_verified_at,
            'is_owner' => (bool) $this->is_owner,
            'is_active' => (bool) $this->is_active,
            'token' => $this->token ?? null,
            'token_type' => $this->token_type ?? 'Bearer',
            'expires_in' => $this->expires_in ?? null,
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'ulid' => $tenant->ulid,
                'type' => $tenant->type,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'status' => $tenant->status,
            ] : null,
            'roles' => $roles ? $roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'template_key' => $role->template_key,
                'is_system' => (bool) $role->is_system,
            ])->values() : [],
            'permissions' => $this->permissionNames(),
            'last_login_at' => $this->last_login_at,
        ];
    }
}
