<?php

namespace Modules\Theaters\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTheaterProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'country_id' => ['sometimes', 'nullable', 'integer', 'exists:settings,id'],
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:settings,id'],
            'timezone' => ['sometimes', 'string', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
            'logo' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'cover' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
