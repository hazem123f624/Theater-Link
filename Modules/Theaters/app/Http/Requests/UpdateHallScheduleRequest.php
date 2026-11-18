<?php

namespace Modules\Theaters\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHallScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'day_of_week' => ['sometimes', 'integer', 'between:1,7'],
            'starts_at' => ['sometimes', 'date_format:H:i'],
            'ends_at' => ['sometimes', 'date_format:H:i'],
            'slot_duration_minutes' => ['sometimes', 'integer', 'min:15', 'max:1440'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
