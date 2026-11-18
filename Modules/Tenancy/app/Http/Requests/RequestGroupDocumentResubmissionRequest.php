<?php

namespace Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestGroupDocumentResubmissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'feedback' => ['required', 'string', 'max:2000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
