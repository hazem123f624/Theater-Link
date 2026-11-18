<?php

namespace Modules\Theaters\Http\Requests;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShowRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->requireTenant()->id;

        return [
            'hall_id' => [
                'sometimes', 'integer',
                Rule::exists('halls', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
