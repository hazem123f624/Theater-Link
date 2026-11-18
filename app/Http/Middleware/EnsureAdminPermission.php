<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Admins\Models\Admins;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = $request->user();

        if (! $admin instanceof Admins) {
            abort(403, __('messages.forbidden'));
        }

        if (! $admin->hasPermission($permission)) {
            abort(403, __('messages.forbidden'));
        }

        return $next($request);
    }
}
