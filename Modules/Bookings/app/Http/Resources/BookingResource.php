<?php

namespace Modules\Bookings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Bookings\Models\Booking;

/** @mixin Booking */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->theaterTenant->theaterProfile;
        $timezone = $profile->timezone ?? config('tenancy.timezone', 'Asia/Riyadh');
        $translation = $this->event->translations->firstWhere('lang', app()->getLocale())
            ?? $this->event->translations->firstWhere('lang', 'en')
            ?? $this->event->translations->first();
        $show = $this->show;
        $cutoffHours = (int) config('tenancy.booking_cancellation_cutoff_hours', 24);

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'currency' => $this->currency,
            'ticket_count' => $this->items->sum('quantity'),
            'confirmed_at' => $this->confirmed_at,
            'cancelled_at' => $this->cancelled_at,
            'cancellation_reason' => $this->cancellation_reason,
            'event' => [
                'id' => $this->event->id,
                'slug' => $this->event->slug,
                'title' => $translation?->title,
                'title_en' => $this->event->translations->firstWhere('lang', 'en')?->title,
                'title_ar' => $this->event->translations->firstWhere('lang', 'ar')?->title,
                'poster_url' => $this->event->poster?->url,
            ],
            'show' => [
                'id' => $show->id,
                'starts_at' => $show->starts_at,
                'ends_at' => $show->ends_at,
                'starts_at_local' => $show->starts_at->copy()->setTimezone($timezone)->format('Y-m-d H:i'),
                'ends_at_local' => $show->ends_at->copy()->setTimezone($timezone)->format('Y-m-d H:i'),
                'timezone' => $timezone,
                'status' => $show->status,
                'cancellation_cutoff_at' => $show->starts_at->copy()->subHours($cutoffHours),
            ],
            'theater' => [
                'id' => $this->theaterTenant->id,
                'name' => $this->theaterTenant->name,
                'subdomain' => $this->theaterTenant->subdomain,
                'logo_url' => $profile?->logo?->url,
                'address' => $profile?->address,
            ],
            'hall' => [
                'id' => $this->hall->id,
                'name' => $this->hall->name,
                'capacity' => $this->hall->capacity,
            ],
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ]),
            'items' => BookingItemResource::collection($this->whenLoaded('items')),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
            'history' => BookingHistoryResource::collection($this->whenLoaded('histories')),
            'created_at' => $this->created_at,
        ];
    }
}
