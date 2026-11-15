<?php

namespace Modules\Tenancy\Http\Middleware;

use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantActive
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->context->requireTenant();

        if (! $tenant->canUseWorkspace()) {
            abort(403, __('tenancy::messages.workspace_not_active'));
        }

        return $next($request);
    }
}
