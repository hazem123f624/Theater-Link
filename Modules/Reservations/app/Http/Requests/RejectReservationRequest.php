<?php

namespace Modules\Reservations\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
