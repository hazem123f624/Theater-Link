<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Modules\Tenancy\Http\Requests\StoreTenantRoleRequest;
use Modules\Tenancy\Http\Requests\UpdateTenantRoleRequest;
use Modules\Tenancy\Models\Permission;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Services\TenantRoleService;

class TenantRoleController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TenantContext $context,
        private TenantRoleService $roles,
    ) {}

    public function store(StoreTenantRoleRequest $request)
    {
        $tenant = $this->context->requireTenant();
        $role = $this->roles->createCustomRole($tenant, $request->validated('name'), $request->validated('permission_ids', []));

        return $this->success(__('tenancy::messages.role_created'), $this->transform($role), 201);
    }

    public function show(TenantRole $role)
    {
        $tenant = $this->context->requireTenant();
        $role = TenantRole::query()
            ->where('tenant_id', $tenant->id)
            ->with(['translations', 'permissions:id,name'])
            ->findOrFail($role->id);

        return $this->success(__('tenancy::messages.roles_retrieved'), $this->transform($role));
    }

    public function update(UpdateTenantRoleRequest $request, TenantRole $role)
    {
        $tenant = $this->context->requireTenant();
        $role = TenantRole::query()->where('tenant_id', $tenant->id)->findOrFail($role->id);
        $role = $this->roles->updateCustomRole(
            $tenant,
            $role,
            $request->validated('name', $role->name),
            $request->validated('permission_ids', $role->permissions()->pluck('tenant_permissions.id')->all())
        );

        return $this->success(__('tenancy::messages.role_updated'), $this->transform($role));
    }

    public function destroy(TenantRole $role)
    {
        $tenant = $this->context->requireTenant();
        $role = TenantRole::query()->where('tenant_id', $tenant->id)->findOrFail($role->id);

        $this->roles->deleteCustomRole($role);

        return $this->success(__('tenancy::messages.role_deleted'));
    }

    public function permissions()
    {
        $tenant = $this->context->requireTenant();

        $permissions = Permission::query()
            ->active()
            ->whereHas('tenantTypes', fn ($query) => $query->where('tenant_type', $tenant->type))
            ->with(['translations' => fn ($query) => $query->where('lang', app()->getLocale())])
            ->orderBy('name')
            ->get()
            ->map(function (Permission $permission): array {
                $translation = $permission->translations->first();

                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'module' => $permission->module,
                    'action' => $permission->action,
                    'title' => $translation ? $translation->title : $permission->name,
                ];
            })
            ->values();

        return $this->success(__('tenancy::messages.roles_retrieved'), $permissions);
    }

    public function index()
    {
        $tenant = $this->context->requireTenant();

        $roles = TenantRole::query()
            ->where('tenant_id', $tenant->id)
            ->with(['translations', 'permissions:id,name'])
            ->orderBy('id')
            ->get()
            ->map(fn (TenantRole $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'template_key' => $role->template_key,
                'is_system' => (bool) $role->is_system,
                'is_active' => (bool) $role->is_active,
                'permissions' => $role->permissions->pluck('name')->values(),
                'permission_ids' => $role->permissions->pluck('id')->values(),
            ]);

        return $this->success(__('tenancy::messages.roles_retrieved'), $roles);
    }
    private function transform(TenantRole $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'template_key' => $role->template_key,
            'is_system' => (bool) $role->is_system,
            'is_active' => (bool) $role->is_active,
            'permissions' => $role->permissions->pluck('name')->values(),
            'permission_ids' => $role->permissions->pluck('id')->values(),
        ];
    }

}
