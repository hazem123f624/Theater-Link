<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Messages\MessageFactory;
use App\Services\OtpService;
use App\Services\Tenancy\TenantContext;
use App\Services\Tenancy\TenantRegistrationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Tenancy\Http\Requests\RegisterGroupRequest;
use Modules\Tenancy\Http\Requests\RegisterTheaterRequest;
use Modules\Tenancy\Http\Requests\ResendTenantRegistrationOtpRequest;
use Modules\Tenancy\Http\Requests\VerifyTenantRegistrationRequest;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;
use Modules\Tenancy\Resources\GroupApplicationResource;
use Modules\Tenancy\Resources\TenantMemberResource;

class TenantRegistrationController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TenantRegistrationService $registration,
        private TenantContext $context,
    ) {}

    public function registerTheater(RegisterTheaterRequest $request)
    {
        $data = $request->validated();
        $phone = $data['phone'];
        $type = 'theater_registration';

        if (! $this->registration->subdomainAvailable($data['subdomain'])) {
            return $this->failure(__('tenancy::messages.subdomain_taken'), 422);
        }

        $data['password'] = Hash::make($data['password']);
        unset($data['password_confirmation']);

        $code = app(OtpService::class)->generateByCache($phone, $type, $data);
        $this->sendOtp($phone, $code);

        return $this->success(__('tenancy::messages.registration_otp_sent'), [
            'otp' => config('app.env') !== 'production' ? $code : null,
        ]);
    }

    public function verifyTheater(VerifyTenantRegistrationRequest $request)
    {
        $payload = $request->validated();
        $data = app(OtpService::class)->verifyByCache($payload['phone'], $payload['otp'], 'theater_registration');

        if (empty($data)) {
            return $this->failure(__('tenancy::messages.invalid_or_expired_otp'), 422);
        }

        $tenant = $this->registration->createTheater($data);
        $owner = $this->ownerOf($tenant);

        return $this->success(
            __('tenancy::messages.registration_completed'),
            $this->authenticatedPayload($tenant, $owner, [
                'profile' => [
                    'name' => $tenant->theaterProfile?->name,
                    'address' => $tenant->theaterProfile?->address,
                    'country_id' => $tenant->theaterProfile?->country_id,
                    'city_id' => $tenant->theaterProfile?->city_id,
                ],
            ])
        );
    }

    public function registerGroup(RegisterGroupRequest $request)
    {
        $data = $request->validated();
        $phone = $data['phone'];
        $type = 'group_registration';
        $permit = $request->file('permit');
        unset($data['permit'], $data['password_confirmation']);

        if (! $this->registration->subdomainAvailable($data['subdomain'])) {
            return $this->failure(__('tenancy::messages.subdomain_taken'), 422);
        }

        $data['password'] = Hash::make($data['password']);
        $data['permit'] = $this->registration->storeTemporaryFile($permit, $phone, $type);

        $code = app(OtpService::class)->generateByCache($phone, $type, $data);
        $this->sendOtp($phone, $code);

        return $this->success(__('tenancy::messages.registration_otp_sent'), [
            'otp' => config('app.env') !== 'production' ? $code : null,
        ]);
    }

    public function verifyGroup(VerifyTenantRegistrationRequest $request)
    {
        $payload = $request->validated();
        $data = app(OtpService::class)->verifyByCache($payload['phone'], $payload['otp'], 'group_registration');

        if (empty($data) || empty($data['permit'])) {
            return $this->failure(__('tenancy::messages.invalid_or_expired_otp'), 422);
        }

        $permit = $this->registration->rehydrateTemporaryFile($data['permit']);
        $result = $this->registration->createGroup($data, $permit);

        $this->registration->cleanupTemporaryFiles($payload['phone'], 'group_registration');
        $owner = $this->ownerOf($result['tenant']);

        return $this->success(
            __('tenancy::messages.registration_completed'),
            $this->authenticatedPayload($result['tenant'], $owner, [
                'application' => new GroupApplicationResource($result['application']),
            ])
        );
    }

    public function resendTheaterOtp(ResendTenantRegistrationOtpRequest $request)
    {
        return $this->resend($request->validated('phone'), 'theater_registration');
    }

    public function resendGroupOtp(ResendTenantRegistrationOtpRequest $request)
    {
        return $this->resend($request->validated('phone'), 'group_registration');
    }

    private function resend(string $phone, string $type)
    {
        if (! Cache::has("otp:cache:{$type}:{$phone}")) {
            return $this->failure(__('tenancy::messages.no_pending_registration'), 422);
        }

        $code = app(OtpService::class)->generateByCache($phone, $type);
        $this->sendOtp($phone, $code);

        return $this->success(__('tenancy::messages.otp_resent'), [
            'otp' => config('app.env') !== 'production' ? $code : null,
        ]);
    }

    private function sendOtp(string $phone, int $code): void
    {
        MessageFactory::make('sms')->send([
            'to' => $phone,
            'body' => __('tenancy::messages.otp_sms', ['code' => $code]),
        ]);
    }

    private function ownerOf(Tenant $tenant): TenantMember
    {
        return $tenant->members()->where('is_owner', true)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function authenticatedPayload(Tenant $tenant, TenantMember $owner, array $extra = []): array
    {
        $this->context->set($tenant, $owner);
        $owner->load(['tenant', 'roles.permissions']);

        $token = $owner->createToken('tenant-member', ['tenant_members'])->plainTextToken;
        $resource = new TenantMemberResource($owner);
        $resource->token = $token;
        $resource->token_type = 'Bearer';
        $resource->expires_in = config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null;

        return array_merge(['member' => $resource], $extra);
    }
}
