<?php

namespace Modules\Tenancy\Http\Middleware;

use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPermission
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $member = $this->context->requireMember();

        if (! $member->hasPermission($permission)) {
            abort(403, __('tenancy::messages.permission_denied'));
        }

        return $next($request);
    }
}
