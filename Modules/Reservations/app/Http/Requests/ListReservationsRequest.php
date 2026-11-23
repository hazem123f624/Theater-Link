<?php

namespace Modules\Reservations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Reservations\Enums\ReservationStatus;

class ListReservationsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(ReservationStatus::values())],
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
