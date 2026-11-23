<?php

namespace Modules\Reservations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'contact_person_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
            'logo' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
