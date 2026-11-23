<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Bookings\Enums\BookingStatus;

class ListBookingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(BookingStatus::values())],
            'scope' => ['nullable', 'string', Rule::in(['upcoming', 'past'])],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
