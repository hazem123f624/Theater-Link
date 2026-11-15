<?php

namespace Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class VerifyTenantRegistrationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => trim((string) $this->input('phone'))]);
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'digits:4'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
