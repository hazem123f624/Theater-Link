<?php

namespace App\Services\Authentication;

use App\Services\Authentication\types\SanctumRepository;

class AuthFactory
{
    /**
     * Create an authentication driver instance for the configured actor type.
     */
    public static function make(string $type, string $guard): AuthInterface
    {
        $guardData = config('auth_guards.types');

        if (! array_key_exists($guard, $guardData)) {
            abort(500, __('messages.invalid_auth_type'));
        }

        return match ($type) {
            'sanctum' => new SanctumRepository($guardData[$guard]),
            default => throw new \Exception('Invalid auth type'),
        };
    }
}
