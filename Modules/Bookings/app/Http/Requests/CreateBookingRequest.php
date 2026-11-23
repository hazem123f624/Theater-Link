<?php

namespace Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'show_id' => ['required', 'integer', 'exists:shows,id'],
            'idempotency_key' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.ticket_tier_id' => ['required', 'integer', 'distinct', 'exists:ticket_tiers,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
