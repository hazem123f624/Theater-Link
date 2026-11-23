<?php

namespace Modules\Reservations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Modules\Reservations\Http\Requests\UpdateGroupProfileRequest;
use Modules\Reservations\Http\Resources\GroupProfileResource;
use Modules\Reservations\Services\GroupProfileService;

class GroupProfileController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TenantContext $context,
        private GroupProfileService $profiles,
    ) {}

    public function show()
    {
        $profile = $this->profiles->forTenant($this->context->requireTenant())->load(['logo', 'tenant']);

        return $this->success(__('reservations::messages.profile_retrieved'), new GroupProfileResource($profile));
    }

    public function update(UpdateGroupProfileRequest $request)
    {
        $profile = $this->profiles->forTenant($this->context->requireTenant());
        $profile = $this->profiles->update($profile, $request->validated(), $request->file('logo'));

        return $this->success(__('reservations::messages.profile_updated'), new GroupProfileResource($profile));
    }
}
