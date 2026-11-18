<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Theaters\Models\Show;

/** @mixin Show */
class ShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'hall_id' => $this->hall_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'event' => $this->whenLoaded('event', fn () => [
                'id' => $this->event->id,
                'title_en' => $this->event->translations->firstWhere('lang', 'en')?->title,
                'title_ar' => $this->event->translations->firstWhere('lang', 'ar')?->title,
            ]),
            'hall' => $this->whenLoaded('hall', fn () => new PublicHallResource($this->hall)),
            'tiers' => $this->whenLoaded('tiers', fn () => TicketTierResource::collection($this->tiers)),
        ];
    }
}
