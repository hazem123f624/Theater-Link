<?php

namespace Modules\Bookings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Theaters\Models\TicketTier;

/** @mixin TicketTier */
class PublicTicketTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $en = $this->translations->firstWhere('lang', 'en');
        $ar = $this->translations->firstWhere('lang', 'ar');
        $current = $this->translations->firstWhere('lang', app()->getLocale()) ?? $en ?? $ar;
        $show = $this->relationLoaded('show') ? $this->show : null;
        $leadMinutes = (int) config('tenancy.booking_lead_minutes', 60);
        $saleNotStarted = $this->sale_starts_at && $this->sale_starts_at->isFuture();
        $saleEnded = $this->sale_ends_at && $this->sale_ends_at->isPast();
        $bookingClosed = $show && $show->starts_at->lessThanOrEqualTo(now()->addMinutes($leadMinutes));
        $available = $this->availableQuantity();

        $saleState = 'available';

        if (! $this->is_active) {
            $saleState = 'inactive';
        } elseif ($saleNotStarted) {
            $saleState = 'not_started';
        } elseif ($saleEnded) {
            $saleState = 'sale_ended';
        } elseif ($bookingClosed) {
            $saleState = 'booking_closed';
        } elseif ($available < 1) {
            $saleState = 'sold_out';
        }

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
            'available_quantity' => $available,
            'sale_starts_at' => $this->sale_starts_at,
            'sale_ends_at' => $this->sale_ends_at,
            'per_booking_limit' => $this->per_booking_limit,
            'is_active' => (bool) $this->is_active,
            'is_on_sale' => $saleState === 'available',
            'can_book' => $saleState === 'available',
            'sale_state' => $saleState,
        ];
    }
}
