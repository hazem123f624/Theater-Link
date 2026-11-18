<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Theaters\Models\HallSchedule;

/** @mixin HallSchedule */
class HallScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hall_id' => $this->hall_id,
            'day_of_week' => $this->day_of_week,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'slot_duration_minutes' => $this->slot_duration_minutes,
            'price' => $this->price,
            'currency' => $this->currency,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
