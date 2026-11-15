<?php

namespace Modules\Tenancy\Http\Middleware;

use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantType
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next, string $type): Response
    {
        $tenant = $this->context->requireTenant();

        if ($tenant->type !== $type) {
            abort(404, __('messages.resource_not_found'));
        }

        return $next($request);
    }
}
