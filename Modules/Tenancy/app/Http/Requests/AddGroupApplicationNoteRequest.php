<?php

namespace Modules\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddGroupApplicationNoteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:5000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
