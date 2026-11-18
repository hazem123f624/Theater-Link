<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Theaters\Models\Hall;

/** @mixin Hall */
class HallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'capacity' => $this->capacity,
            'is_active' => (bool) $this->is_active,
            'schedules_count' => $this->whenCounted('schedules'),
            'created_at' => $this->created_at,
        ];
    }
}
