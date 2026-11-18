<?php

namespace Modules\Theaters\Http\Requests;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHallRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->requireTenant()->id;
        $hallId = $this->route('hall');

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('halls', 'name')->where('tenant_id', $tenantId)->whereNull('deleted_at')->ignore($hallId),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
