<?php

namespace Modules\Reservations\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Reservations\Models\Reservation;

/** @mixin Reservation */
class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $theaterProfile = $this->theaterTenant->theaterProfile;
        $groupProfile = $this->groupTenant->groupProfile;
        $slot = $this->slot;
        $timezone = $theaterProfile->timezone ?? 'Asia/Riyadh';

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'price' => $this->price,
            'currency' => $this->currency,
            'hold_expires_at' => $this->hold_expires_at,
            'approved_at' => $this->approved_at,
            'rejected_at' => $this->rejected_at,
            'cancelled_at' => $this->cancelled_at,
            'completed_at' => $this->completed_at,
            'rejection_reason' => $this->rejection_reason,
            'cancellation_reason' => $this->cancellation_reason,
            'theater' => [
                'id' => $this->theaterTenant->id,
                'name' => $this->theaterTenant->name,
                'subdomain' => $this->theaterTenant->subdomain,
                'logo_url' => $theaterProfile?->logo?->url,
                'address' => $theaterProfile?->address,
            ],
            'group' => [
                'id' => $this->groupTenant->id,
                'name' => $this->groupTenant->name,
                'subdomain' => $this->groupTenant->subdomain,
                'logo_url' => $groupProfile?->logo?->url,
                'contact_person_name' => $groupProfile?->contact_person_name,
                'email' => $groupProfile?->email,
                'phone' => $groupProfile?->phone,
            ],
            'hall' => $this->hall ? [
                'id' => $this->hall->id,
                'name' => $this->hall->name,
                'capacity' => $this->hall->capacity,
            ] : null,
            'slot' => $slot ? [
                'id' => $slot->id,
                'starts_at' => $slot->starts_at,
                'ends_at' => $slot->ends_at,
                'starts_at_local' => $slot->starts_at->copy()->setTimezone($timezone)->format('Y-m-d H:i'),
                'ends_at_local' => $slot->ends_at->copy()->setTimezone($timezone)->format('Y-m-d H:i'),
                'timezone' => $timezone,
            ] : null,
            'requested_by' => $this->requester ? ['id' => $this->requester->id, 'name' => $this->requester->name] : null,
            'history' => ReservationHistoryResource::collection(
                $this->relationLoaded('histories') ? $this->histories : collect()
            ),
            'created_at' => $this->created_at,
        ];
    }
}
