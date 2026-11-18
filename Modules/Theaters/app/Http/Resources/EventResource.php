<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Theaters\Models\Event;

/** @mixin Event */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $en = $this->translations->firstWhere('lang', 'en');
        $ar = $this->translations->firstWhere('lang', 'ar');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'status' => $this->status,
            'category' => $this->category ? new EventCategoryResource($this->category) : null,
            'poster_url' => $this->poster?->url,
            'title_en' => $en?->title,
            'title_ar' => $ar?->title,
            'description_en' => $en?->description,
            'description_ar' => $ar?->description,
            'published_at' => $this->published_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'shows' => $this->whenLoaded('shows', fn () => ShowResource::collection($this->shows)),
        ];
    }
}
