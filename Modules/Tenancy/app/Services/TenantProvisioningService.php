<?php

namespace Modules\Tenancy\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Tenancy\Enums\TenantStatus;
use Modules\Tenancy\Enums\TenantType;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;

class TenantProvisioningService
{
    public function __construct(private TenantRoleService $roles) {}

    /**
     * Create a tenant, its owner, and its system roles in one transaction.
     *
     * @param  array{type: string, name: string, subdomain: string}  $tenantData
     * @param  array{name: string, email: string, phone?: string|null, password: string}  $ownerData
     */
    public function provision(array $tenantData, array $ownerData): Tenant
    {
        $type = TenantType::from($tenantData['type']);
        $subdomain = $this->normalizeSubdomain($tenantData['subdomain']);

        if (Tenant::withTrashed()->where('subdomain', $subdomain)->exists()) {
            throw ValidationException::withMessages([
                'subdomain' => [__('tenancy::messages.subdomain_taken')],
            ]);
        }

        return DB::transaction(function () use ($type, $tenantData, $ownerData, $subdomain): Tenant {
            $tenant = Tenant::create([
                'type' => $type->value,
                'name' => $tenantData['name'],
                'subdomain' => $subdomain,
                'status' => $type === TenantType::GROUP
                    ? TenantStatus::PENDING_REVIEW->value
                    : TenantStatus::ACTIVE->value,
                'approved_at' => $type === TenantType::THEATER ? now() : null,
            ]);

            $owner = TenantMember::create([
                'tenant_id' => $tenant->id,
                'name' => $ownerData['name'],
                'email' => Str::lower($ownerData['email']),
                'phone' => $ownerData['phone'] ?? null,
                'password' => $ownerData['password'],
                'is_owner' => true,
                'is_active' => true,
            ]);

            $this->roles->provisionSystemRoles($tenant);
            $this->roles->assignOwnerRole($tenant, $owner);

            return $tenant->fresh(['members', 'roles']);
        });
    }

    public function approve(Tenant $tenant): Tenant
    {
        if ($tenant->type !== TenantType::GROUP->value) {
            throw new \LogicException('Only group tenants require approval.');
        }

        return DB::transaction(function () use ($tenant): Tenant {
            $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);

            if ($tenant->status === TenantStatus::ACTIVE->value) {
                return $tenant;
            }

            $tenant->update([
                'status' => TenantStatus::ACTIVE->value,
                'approved_at' => now(),
            ]);

            return $tenant->fresh();
        });
    }

    private function normalizeSubdomain(string $subdomain): string
    {
        return Str::lower(trim($subdomain));
    }
}
