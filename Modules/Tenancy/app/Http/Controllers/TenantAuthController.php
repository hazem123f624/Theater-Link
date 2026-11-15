<?php

namespace Modules\Tenancy\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Security\Services\ApiKeyService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Tenancy\Enums\TenantStatus;
use Modules\Tenancy\Enums\TenantType;
use Modules\Tenancy\Http\Requests\TenantLoginRequest;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;
use Modules\Tenancy\Resources\TenantMemberResource;

class TenantAuthController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private TenantContext $context) {}

    public function loginTheater(TenantLoginRequest $request)
    {
        return $this->loginForType($request, TenantType::THEATER);
    }

    public function loginGroup(TenantLoginRequest $request)
    {
        return $this->loginForType($request, TenantType::GROUP);
    }

    private function loginForType(TenantLoginRequest $request, TenantType $type)
    {
        $data = $request->validated();

        $tenant = Tenant::query()
            ->where('subdomain', $data['subdomain'])
            ->where('type', $type->value)
            ->first();

        if (! $tenant) {
            return $this->failure(__('tenancy::messages.invalid_credentials'), 422);
        }

        /** @var TenantMember|null $member */
        $member = TenantMember::query()
            ->where('tenant_id', $tenant->id)
            ->where('email', $data['email'])
            ->active()
            ->first();

        if (! $member) {
            return $this->failure(__('tenancy::messages.invalid_credentials'), 422);
        }

        if ($member->locked_until && $member->locked_until->isFuture()) {
            return $this->failure(__('tenancy::messages.account_locked'), 422);
        }

        if (! Hash::check($data['password'], $member->password)) {
            $this->recordFailedAttempt($member);

            return $this->failure(__('tenancy::messages.invalid_credentials'), 422);
        }

        // A pending theater is not allowed into the workspace. A pending group
        // may log in, but its workspace routes stay blocked by tenant.active.
        if ($tenant->status !== TenantStatus::ACTIVE->value
            && ! ($type === TenantType::GROUP && $tenant->status === TenantStatus::PENDING_REVIEW->value)) {
            return $this->failure(__('tenancy::messages.workspace_not_active'), 403);
        }

        $member->update([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        $this->context->set($tenant, $member);
        $member->load(['tenant', 'roles.permissions']);

        $token = $member->createToken('tenant-member', ['tenant_members'])->plainTextToken;

        $resource = new TenantMemberResource($member);
        $resource->token = $token;
        $resource->token_type = 'Bearer';
        $resource->expires_in = config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null;

        return $this->success(__('tenancy::messages.logged_in'), data: $resource);
    }

    private function recordFailedAttempt(TenantMember $member): void
    {
        $member->increment('failed_login_attempts');
        $member->refresh();

        $lock = match (true) {
            $member->failed_login_attempts >= 15 => now()->addDay(),
            $member->failed_login_attempts >= 10 => now()->addHour(),
            $member->failed_login_attempts >= 5 => now()->addMinutes(15),
            default => null,
        };

        if ($lock) {
            $member->update(['locked_until' => $lock]);
        }
    }

    public function resolve(Request $request)
    {
        $subdomain = $request->header('X-Subdomain') ?: $request->query('subdomain');

        if (empty($subdomain)) {
            return $this->failure(__('tenancy::messages.subdomain_required'), 422);
        }

        $tenant = Tenant::query()->where('subdomain', $subdomain)->active()->first();

        if (! $tenant || empty($tenant->uuid) || empty($tenant->ulid)) {
            return $this->failure(__('messages.resource_not_found'), 404);
        }

        return $this->success(__('tenancy::messages.tenant_resolved'), [
            'id' => ApiKeyService::encrypt($tenant->ulid, $tenant->uuid),
            'domain' => $tenant->subdomain,
            'name' => $tenant->name,
            'logo' => null,
        ]);
    }

    public function me(Request $request)
    {
        /** @var TenantMember $member */
        $member = $request->user();
        $member->load(['tenant', 'roles.permissions']);

        return $this->success(__('tenancy::messages.profile_retrieved'), new TenantMemberResource($member));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(__('messages.logged_out'));
    }

    public function refreshToken(Request $request)
    {
        /** @var TenantMember $member */
        $member = $request->user();
        $member->currentAccessToken()->delete();

        $token = $member->createToken('tenant-member', ['tenant_members'])->plainTextToken;
        $member->load(['tenant', 'roles.permissions']);
        $resource = new TenantMemberResource($member);
        $resource->token = $token;
        $resource->expires_in = config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null;

        return $this->success(__('messages.token_issued'), $resource);
    }
}
