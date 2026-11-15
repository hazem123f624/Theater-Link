<?php

namespace Modules\Tenancy\Http\Requests;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenancy\Models\TenantRole;

class UpdateTenantRoleRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->requireTenant()->id;
        /** @var TenantRole|null $role */
        $role = $this->route('role');

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('tenant_roles', 'name')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')
                    ->ignore($role?->id),
            ],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('tenant_permissions', 'id')->where('is_active', true)],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
