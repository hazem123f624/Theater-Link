<?php

namespace Modules\Theaters\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListHallSlotsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string', Rule::in(['available', 'held', 'reserved', 'blocked', 'completed'])],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
