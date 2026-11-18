<?php

namespace Modules\Theaters\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'description_en' => ['nullable', 'string', 'max:10000'],
            'description_ar' => ['nullable', 'string', 'max:10000'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'category_id' => ['nullable', 'integer', 'exists:event_categories,id'],
            'poster' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
