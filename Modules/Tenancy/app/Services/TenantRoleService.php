<?php

namespace Modules\Tenancy\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Tenancy\Models\Permission;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;
use Modules\Tenancy\Models\TenantMemberRole;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Models\TenantRolePermission;
use Modules\Tenancy\Models\TenantRoleTranslation;

class TenantRoleService
{
    /**
     * Create the system roles for a tenant and attach the allowed permissions.
     *
     * @return array<string, TenantRole>
     */
    public function provisionSystemRoles(Tenant $tenant): array
    {
        $definitions = config("tenancy.system_roles.{$tenant->type}", []);
        $roles = [];

        DB::transaction(function () use ($tenant, $definitions, &$roles): void {
            foreach ($definitions as $templateKey => $definition) {
                $role = TenantRole::withoutGlobalScopes()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'template_key' => $templateKey],
                    [
                        'name' => $definition['name'],
                        'is_system' => true,
                        'is_active' => true,
                    ]
                );

                $roles[$templateKey] = $role;

                foreach (['en', 'ar'] as $lang) {
                    TenantRoleTranslation::updateOrCreate(
                        ['role_id' => $role->id, 'lang' => $lang],
                        ['name' => $definition['name'], 'description' => null]
                    );
                }

                $permissionNames = $definition['permissions'] === '*'
                    ? $this->allowedPermissionNames($tenant)
                    : $definition['permissions'];

                $permissions = Permission::query()
                    ->whereIn('name', $permissionNames)
                    ->where('is_active', true)
                    ->get();

                foreach ($permissions as $permission) {
                    TenantRolePermission::withoutGlobalScopes()->updateOrCreate(
                        ['role_id' => $role->id, 'permission_id' => $permission->id],
                        ['tenant_id' => $tenant->id]
                    );
                }
            }
        });

        return $roles;
    }

    public function createCustomRole(Tenant $tenant, string $name, array $permissionIds = []): TenantRole
    {
        return DB::transaction(function () use ($tenant, $name, $permissionIds): TenantRole {
            $role = TenantRole::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'is_system' => false,
                'is_active' => true,
            ]);

            $this->writeTranslations($role, $name);
            $this->syncPermissions($tenant, $role, $permissionIds);

            return $role->load(['permissions', 'translations']);
        });
    }

    public function updateCustomRole(Tenant $tenant, TenantRole $role, string $name, array $permissionIds = []): TenantRole
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => [__('tenancy::messages.system_role_protected')],
            ]);
        }

        return DB::transaction(function () use ($tenant, $role, $name, $permissionIds): TenantRole {
            $role->update(['name' => $name]);
            $this->writeTranslations($role, $name);
            $this->syncPermissions($tenant, $role, $permissionIds);

            return $role->fresh(['permissions', 'translations']);
        });
    }

    public function deleteCustomRole(TenantRole $role): void
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => [__('tenancy::messages.system_role_protected')],
            ]);
        }

        DB::transaction(fn () => $role->delete());
    }

    private function writeTranslations(TenantRole $role, string $name): void
    {
        foreach (['en', 'ar'] as $lang) {
            TenantRoleTranslation::updateOrCreate(
                ['role_id' => $role->id, 'lang' => $lang],
                ['name' => $name, 'description' => null]
            );
        }
    }

    private function syncPermissions(Tenant $tenant, TenantRole $role, array $permissionIds): void
    {
        $permissionIds = collect($permissionIds)->map(fn ($id) => (int) $id)->unique()->values();

        $permissions = Permission::query()
            ->active()
            ->whereIn('id', $permissionIds)
            ->whereHas('tenantTypes', fn ($query) => $query->where('tenant_type', $tenant->type))
            ->get();

        if ($permissions->count() !== $permissionIds->count()) {
            throw ValidationException::withMessages([
                'permission_ids' => [__('tenancy::messages.invalid_tenant_permission')],
            ]);
        }

        TenantRolePermission::withoutGlobalScopes()
            ->where('role_id', $role->id)
            ->when($permissionIds->isNotEmpty(), fn ($query) => $query->whereNotIn('permission_id', $permissionIds))
            ->when($permissionIds->isEmpty(), fn ($query) => $query)
            ->delete();

        foreach ($permissions as $permission) {
            TenantRolePermission::withoutGlobalScopes()->updateOrCreate(
                ['role_id' => $role->id, 'permission_id' => $permission->id],
                ['tenant_id' => $tenant->id]
            );
        }
    }

    public function assignOwnerRole(Tenant $tenant, TenantMember $member): void
    {
        $ownerRole = TenantRole::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('template_key', 'owner')
            ->first();

        if (! $ownerRole) {
            return;
        }

        TenantMemberRole::withoutGlobalScopes()->updateOrCreate(
            ['member_id' => $member->id, 'role_id' => $ownerRole->id],
            ['tenant_id' => $tenant->id]
        );
    }

    /**
     * @return array<int, string>
     */
    private function allowedPermissionNames(Tenant $tenant): array
    {
        return Permission::query()
            ->active()
            ->whereHas('tenantTypes', fn ($query) => $query->where('tenant_type', $tenant->type))
            ->pluck('name')
            ->all();
    }
}
