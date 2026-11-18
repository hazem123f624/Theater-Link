<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Theaters\Models\Event;

/** @mixin Event */
class PublicEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translation = $this->translations->firstWhere('lang', app()->getLocale())
            ?? $this->translations->firstWhere('lang', 'en')
            ?? $this->translations->first();
        $profile = $this->tenant->theaterProfile;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $translation?->title,
            'description' => $translation?->description,
            'poster_url' => $this->poster?->url,
            'category' => $this->category ? new EventCategoryResource($this->category) : null,
            'theater' => [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'logo_url' => $profile?->logo?->url,
                'address' => $profile?->address,
            ],
            'published_at' => $this->published_at,
            'shows' => $this->whenLoaded('shows', fn () => ShowResource::collection($this->shows)),
        ];
    }
}
