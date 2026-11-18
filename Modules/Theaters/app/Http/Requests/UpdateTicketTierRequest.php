<?php

namespace Modules\Theaters\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketTierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description_en' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'description_ar' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'total_quantity' => ['sometimes', 'integer', 'min:1'],
            'sale_starts_at' => ['sometimes', 'nullable', 'date'],
            'sale_ends_at' => ['sometimes', 'nullable', 'date'],
            'per_booking_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
