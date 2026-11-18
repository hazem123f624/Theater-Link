<?php

namespace Modules\Theaters\Http\Requests;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShowRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->requireTenant()->id;

        return [
            'hall_id' => [
                'required', 'integer',
                Rule::exists('halls', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
