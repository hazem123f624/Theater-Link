<?php

namespace Modules\Theaters\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHallScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'slot_duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
