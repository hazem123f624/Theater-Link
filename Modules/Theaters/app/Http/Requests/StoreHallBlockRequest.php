<?php

namespace Modules\Theaters\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHallBlockRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
