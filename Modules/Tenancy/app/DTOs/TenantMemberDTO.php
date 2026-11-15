<?php

namespace Modules\Tenancy\DTOs;

use App\DTOs\BaseDto;

class TenantMemberDTO extends BaseDto
{
    public function __construct(
        public int $id,
        public int $tenant_id,
        public string $name,
        public string $email,
        public ?string $phone,
        public bool $is_owner,
        public bool $is_active,
        public mixed $tenant = null,
        public mixed $roles = null,
    ) {}

    public static function fromModel($model): self
    {
        return new self(
            id: $model->id,
            tenant_id: $model->tenant_id,
            name: $model->name,
            email: $model->email,
            phone: $model->phone,
            is_owner: (bool) $model->is_owner,
            is_active: (bool) $model->is_active,
            tenant: $model->relationLoaded('tenant') ? $model->tenant : null,
            roles: $model->relationLoaded('roles') ? $model->roles : null,
        );
    }
}
