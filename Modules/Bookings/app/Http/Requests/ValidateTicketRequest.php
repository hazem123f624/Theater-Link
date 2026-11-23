<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'qr_token' => ['required', 'string', 'max:100'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
