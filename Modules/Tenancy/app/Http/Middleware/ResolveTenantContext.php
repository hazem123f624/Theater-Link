<?php

namespace Modules\Tenancy\Http\Middleware;

use App\Services\Security\Services\ApiKeyService;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user();

        if (! $member instanceof TenantMember) {
            abort(403, __('tenancy::messages.tenant_member_required'));
        }

        if (! $member->is_active) {
            abort(403, __('tenancy::messages.account_inactive'));
        }

        $tenant = Tenant::query()->find($member->tenant_id);

        if (! $tenant || $tenant->trashed()) {
            abort(404, __('messages.resource_not_found'));
        }

        $headerSubdomain = $request->header('X-Subdomain');
        if ($headerSubdomain && strcasecmp($headerSubdomain, $tenant->subdomain) !== 0) {
            abort(404, __('messages.resource_not_found'));
        }

        $headerTenant = $request->header('X-Tenant');
        if ($headerTenant) {
            $isPlainUlid = hash_equals((string) $tenant->ulid, (string) $headerTenant);
            $decrypted = $isPlainUlid ? null : ApiKeyService::decrypt((string) $headerTenant, (string) $tenant->uuid);
            $isEncryptedUlid = $decrypted !== null && hash_equals((string) $tenant->ulid, $decrypted);

            if (! $isPlainUlid && ! $isEncryptedUlid) {
                abort(404, __('messages.resource_not_found'));
            }
        }

        $this->context->set($tenant, $member);

        return $next($request);
    }
}
