<?php

namespace Modules\Theaters\Services;

use App\Services\MediaSystem\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TheaterProfile;

class TheaterProfileService
{
    public function __construct(private MediaService $media) {}

    public function forTenant(Tenant $tenant): TheaterProfile
    {
        return TheaterProfile::query()->firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'name' => $tenant->name,
                'timezone' => config('tenancy.timezone', 'Asia/Riyadh'),
                'is_active' => true,
            ]
        );
    }

    public function update(TheaterProfile $profile, array $data, ?UploadedFile $logo = null, ?UploadedFile $cover = null): TheaterProfile
    {
        return DB::transaction(function () use ($profile, $data, $logo, $cover): TheaterProfile {
            $profile->fill(collect($data)->only([
                'name',
                'description',
                'email',
                'phone',
                'address',
                'country_id',
                'city_id',
                'timezone',
                'is_active',
            ])->all());

            if ($logo) {
                $media = $this->media
                    ->useDisk(config('filesystems.default'))
                    ->withVisibility('public')
                    ->withCategory('theater_logo')
                    ->upload($logo, $profile, 'logo', "theaters/{$profile->tenant_id}/profile");
                $profile->logo_media_id = $media->id;
            }

            if ($cover) {
                $media = $this->media
                    ->useDisk(config('filesystems.default'))
                    ->withVisibility('public')
                    ->withCategory('theater_cover')
                    ->upload($cover, $profile, 'cover', "theaters/{$profile->tenant_id}/profile");
                $profile->cover_media_id = $media->id;
            }

            $profile->save();

            return $profile->fresh(['logo', 'cover', 'tenant']);
        });
    }
}
