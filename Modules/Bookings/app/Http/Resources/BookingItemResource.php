<?php

namespace Modules\Bookings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Bookings\Models\BookingItem;

/** @mixin BookingItem */
class BookingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = app()->getLocale() === 'ar'
            ? ($this->tier_name_ar ?? $this->tier_name_en)
            : ($this->tier_name_en ?? $this->tier_name_ar);

        return [
            'id' => $this->id,
            'ticket_tier_id' => $this->ticket_tier_id,
            'name' => $name,
            'name_en' => $this->tier_name_en,
            'name_ar' => $this->tier_name_ar,
            'unit_price' => $this->unit_price,
            'currency' => $this->currency,
            'quantity' => $this->quantity,
            'line_total' => $this->line_total,
        ];
    }
}
