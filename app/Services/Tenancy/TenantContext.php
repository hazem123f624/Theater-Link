<?php

namespace App\Services\Tenancy;

use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;

class TenantContext
{
    private ?Tenant $tenant = null;

    private ?TenantMember $member = null;

    public function set(Tenant $tenant, TenantMember $member): void
    {
        $this->tenant = $tenant;
        $this->member = $member;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->member = null;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function member(): ?TenantMember
    {
        return $this->member;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function type(): ?string
    {
        return $this->tenant?->type;
    }

    public function requireTenant(): Tenant
    {
        if (! $this->tenant) {
            abort(403, __('tenancy::messages.tenant_context_missing'));
        }

        return $this->tenant;
    }

    public function requireMember(): TenantMember
    {
        if (! $this->member) {
            abort(403, __('tenancy::messages.tenant_context_missing'));
        }

        return $this->member;
    }
}
