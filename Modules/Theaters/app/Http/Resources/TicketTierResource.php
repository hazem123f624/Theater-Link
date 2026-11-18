<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Theaters\Models\TicketTier;

/** @mixin TicketTier */
class TicketTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $en = $this->translations->firstWhere('lang', 'en');
        $ar = $this->translations->firstWhere('lang', 'ar');
        $current = $this->translations->firstWhere('lang', app()->getLocale()) ?? $en ?? $ar;

        return [
            'id' => $this->id,
            'show_id' => $this->show_id,
            'name' => $current?->name,
            'name_en' => $en?->name,
            'name_ar' => $ar?->name,
            'description_en' => $en?->description,
            'description_ar' => $ar?->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'total_quantity' => $this->total_quantity,
            'sold_quantity' => $this->sold_quantity,
            'held_quantity' => $this->held_quantity,
            'available_quantity' => $this->availableQuantity(),
            'sale_starts_at' => $this->sale_starts_at,
            'sale_ends_at' => $this->sale_ends_at,
            'per_booking_limit' => $this->per_booking_limit,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
