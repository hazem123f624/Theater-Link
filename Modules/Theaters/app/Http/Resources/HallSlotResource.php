<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Theaters\Models\HallTimeSlot;

/** @mixin HallTimeSlot */
class HallSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $timezone = $this->hall?->tenant->theaterProfile->timezone ?? 'Asia/Riyadh';

        return [
            'id' => $this->id,
            'hall_id' => $this->hall_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'starts_at_local' => $this->starts_at->copy()->setTimezone($timezone)->format('Y-m-d H:i'),
            'ends_at_local' => $this->ends_at->copy()->setTimezone($timezone)->format('Y-m-d H:i'),
            'timezone' => $timezone,
            'status' => $this->status,
            'price' => $this->price,
            'currency' => $this->currency,
            'show_id' => $this->show_id,
            'block_id' => $this->block_id,
        ];
    }
}
