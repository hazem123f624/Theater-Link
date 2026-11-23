<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCancelBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
