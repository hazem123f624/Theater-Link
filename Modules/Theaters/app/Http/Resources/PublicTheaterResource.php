<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Models\Tenant;

/** @mixin Tenant */
class PublicTheaterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->theaterProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'subdomain' => $this->subdomain,
            'description' => $profile?->description,
            'address' => $profile?->address,
            'city' => $profile?->city ? ['id' => $profile->city->id, 'name_en' => $profile->city->name_en, 'name_ar' => $profile->city->name_ar] : null,
            'country' => $profile?->country ? ['id' => $profile->country->id, 'name_en' => $profile->country->name_en, 'name_ar' => $profile->country->name_ar] : null,
            'logo_url' => $profile?->logo?->url,
            'cover_url' => $profile?->cover?->url,
            'timezone' => $profile?->timezone,
            'halls_count' => $this->whenCounted('halls'),
            'halls' => $this->whenLoaded('halls', fn () => PublicHallResource::collection($this->halls)),
        ];
    }
}
