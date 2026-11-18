<?php

namespace Modules\Theaters\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicEventListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'theater_id' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
