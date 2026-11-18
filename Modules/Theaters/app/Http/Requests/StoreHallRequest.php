<?php

namespace Modules\Theaters\Http\Requests;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHallRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->requireTenant()->id;

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('halls', 'name')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:3000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
