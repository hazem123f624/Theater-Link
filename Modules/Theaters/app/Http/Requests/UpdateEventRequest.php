<?php

namespace Modules\Theaters\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title_en' => ['sometimes', 'required', 'string', 'max:255'],
            'title_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description_en' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'description_ar' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:event_categories,id'],
            'poster' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
