<?php

namespace Modules\Tenancy\Http\Requests;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRoleRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->requireTenant()->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('tenant_roles', 'name')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('tenant_permissions', 'id')->where('is_active', true)],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
