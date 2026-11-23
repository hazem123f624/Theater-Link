<?php

namespace Modules\Reservations\Services;

use App\Services\MediaSystem\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Models\GroupProfile;
use Modules\Tenancy\Models\Tenant;

class GroupProfileService
{
    public function __construct(private MediaService $media) {}

    public function forTenant(Tenant $tenant): GroupProfile
    {
        return GroupProfile::query()->firstOrCreate(
            ['tenant_id' => $tenant->id],
            ['name' => $tenant->name, 'is_active' => true]
        );
    }

    public function update(GroupProfile $profile, array $data, ?UploadedFile $logo = null): GroupProfile
    {
        return DB::transaction(function () use ($profile, $data, $logo): GroupProfile {
            $profile->fill(collect($data)->only([
                'name', 'description', 'contact_person_name', 'email', 'phone', 'is_active',
            ])->all());

            if ($logo) {
                $media = $this->media
                    ->useDisk(config('filesystems.default'))
                    ->withVisibility('public')
                    ->withCategory('group_logo')
                    ->upload($logo, $profile, 'logo', "groups/{$profile->tenant_id}/profile");
                $profile->logo_media_id = $media->id;
            }

            $profile->save();

            return $profile->fresh(['logo', 'tenant']);
        });
    }
}
