<?php

namespace Modules\Tenancy\Http\Requests;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreTenantInvitationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->requireTenant()->id;

        return [
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('tenant_members', 'email')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'role_id' => [
                'nullable', 'integer',
                Rule::exists('tenant_roles', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
