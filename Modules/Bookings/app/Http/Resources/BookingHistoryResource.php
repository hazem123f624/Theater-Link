<?php

namespace Modules\Bookings\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Bookings\Models\BookingStatusHistory;

/** @mixin BookingStatusHistory */
class BookingHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'action' => $this->action,
            'actor_type' => $this->actor_type,
            'actor_id' => $this->actor_id,
            'note' => $this->note,
            'created_at' => $this->created_at,
        ];
    }
}
