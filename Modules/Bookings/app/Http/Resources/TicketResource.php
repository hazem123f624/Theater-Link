<?php

namespace Modules\Bookings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Bookings\Models\Ticket;

/** @mixin Ticket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $item = $this->relationLoaded('bookingItem') ? $this->bookingItem : null;
        $name = null;

        if ($item) {
            $name = app()->getLocale() === 'ar'
                ? ($item->tier_name_ar ?? $item->tier_name_en)
                : ($item->tier_name_en ?? $item->tier_name_ar);
        }

        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'qr_token' => $this->qr_token,
            'status' => $this->status,
            'sequence' => $this->sequence,
            'tier_name' => $name,
            'used_at' => $this->used_at,
            'cancelled_at' => $this->cancelled_at,
            'expired_at' => $this->expired_at,
            'created_at' => $this->created_at,
        ];
    }
}
