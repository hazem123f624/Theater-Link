<?php

namespace Modules\Theaters\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tenancy\Models\TheaterProfile;

/** @mixin TheaterProfile */
class TheaterProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'description' => $this->description,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'country_id' => $this->country_id,
            'city_id' => $this->city_id,
            'country' => $this->country ? ['id' => $this->country->id, 'name_en' => $this->country->name_en, 'name_ar' => $this->country->name_ar] : null,
            'city' => $this->city ? ['id' => $this->city->id, 'name_en' => $this->city->name_en, 'name_ar' => $this->city->name_ar] : null,
            'timezone' => $this->timezone,
            'logo_url' => $this->logo?->url,
            'cover_url' => $this->cover?->url,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
