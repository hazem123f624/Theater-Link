<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Theaters\Models\EventCategory;

/** @mixin EventCategory */
class EventCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $translation = $this->translations->firstWhere('lang', app()->getLocale()) ?? $this->translations->first();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $translation?->name,
        ];
    }
}
