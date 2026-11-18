<?php

namespace Modules\Theaters\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Modules\Theaters\Http\Requests\UpdateTheaterProfileRequest;
use Modules\Theaters\Http\Resources\TheaterProfileResource;
use Modules\Theaters\Services\TheaterProfileService;

class TheaterProfileController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TenantContext $context,
        private TheaterProfileService $profiles,
    ) {}

    public function show()
    {
        $profile = $this->profiles->forTenant($this->context->requireTenant())
            ->load(['logo', 'cover', 'country', 'city']);

        return $this->success(__('theaters::messages.profile_retrieved'), new TheaterProfileResource($profile));
    }

    public function update(UpdateTheaterProfileRequest $request)
    {
        $data = $request->validated();
        $profile = $this->profiles->forTenant($this->context->requireTenant());

        $profile = $this->profiles->update(
            $profile,
            $data,
            $request->file('logo'),
            $request->file('cover')
        );

        return $this->success(__('theaters::messages.profile_updated'), new TheaterProfileResource($profile));
    }
}
