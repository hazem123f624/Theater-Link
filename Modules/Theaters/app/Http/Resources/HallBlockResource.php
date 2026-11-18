<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Theaters\Models\HallBlock;

/** @mixin HallBlock */
class HallBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hall_id' => $this->hall_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'reason' => $this->reason,
        ];
    }
}
