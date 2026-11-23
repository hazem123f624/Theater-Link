<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TheaterCancelBookingRequest extends FormRequest
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
