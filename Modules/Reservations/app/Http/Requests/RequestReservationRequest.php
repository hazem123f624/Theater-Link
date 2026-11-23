<?php

namespace Modules\Reservations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'hall_time_slot_id' => ['required', 'integer', 'exists:hall_time_slots,id'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
