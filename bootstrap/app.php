<?php

use App\Services\Security\Middleware\ApiKeyMiddleware;
use App\Services\Security\Middleware\CorsMiddleware;
use App\Services\Security\Middleware\ForceJsonMiddleware;
use App\Services\Security\Middleware\IpFilterMiddleware;
use App\Services\Security\Middleware\NonceMiddleware;
use App\Services\Security\Middleware\PayloadSizeLimitMiddleware;
use App\Services\Security\Middleware\RateLimitMiddleware;
use App\Services\Security\Middleware\SecurityHeadersMiddleware;
use App\Services\Security\Middleware\SignatureMiddleware;
use App\Services\Security\Middleware\ThreatDetectionMiddleware;
use App\Services\Security\Middleware\TimestampAntiReplayMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/security-api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SetLocaleFromHeader::class);

        $middleware->redirectGuestsTo(fn () => route('api.login'));

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'security.threat' => ThreatDetectionMiddleware::class,
            'security.nonce' => NonceMiddleware::class,
            'tenant' => \Modules\Tenancy\Http\Middleware\ResolveTenantContext::class,
            'tenant.active' => \Modules\Tenancy\Http\Middleware\EnsureTenantActive::class,
            'tenant.type' => \Modules\Tenancy\Http\Middleware\EnsureTenantType::class,
            'permission' => \Modules\Tenancy\Http\Middleware\EnsureTenantPermission::class,
        ]);

        $middleware->appendToGroup('security', [
            ThreatDetectionMiddleware::class,
            IpFilterMiddleware::class,
            RateLimitMiddleware::class,
            ForceJsonMiddleware::class,
            PayloadSizeLimitMiddleware::class,
            SecurityHeadersMiddleware::class,
            CorsMiddleware::class,
            ApiKeyMiddleware::class,
            TimestampAntiReplayMiddleware::class,
            SignatureMiddleware::class,
            NonceMiddleware::class,
        ]);

        $middleware->appendToGroup('security-public', [
            ThreatDetectionMiddleware::class,
            IpFilterMiddleware::class,
            RateLimitMiddleware::class,
            ForceJsonMiddleware::class,
            PayloadSizeLimitMiddleware::class,
            SecurityHeadersMiddleware::class,
            CorsMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $helper = new class
        {
            use App\Http\Traits\HttpResponsesTrait;
        };

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json(['success' => false, 'message' => __('messages.unauthenticated')], 401);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) use ($helper) {
            $errors = $e->errors();
            $firstMessage = reset($errors);
            $firstMessageText = is_array($firstMessage) ? ($firstMessage[0] ?? '') : $firstMessage;

            return $helper->errors(
                message: $firstMessageText ?: $e->getMessage(),
                data: $errors,
                status: 422,
            );
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) use ($helper) {
            if ($request->expectsJson()) {
                return $helper->failure(__('messages.resource_not_found'), 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) use ($helper) {
            $status = $e->getStatusCode();

            return match ($status) {
                401 => $helper->failure(__('messages.unauthorized'), 401),
                403 => $helper->failure($e->getMessage() ?: __('messages.forbidden'), 403),
                404 => $helper->failure(__('messages.resource_not_found'), 404),
                405 => $helper->failure(__('messages.method_not_allowed'), 405),
                419 => $helper->failure(__('messages.page_expired'), 419),
                default => $helper->failure(__('messages.server_error'), 500),
            };
        });
    })
    ->create();
