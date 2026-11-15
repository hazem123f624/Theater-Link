<?php

namespace App\Providers;

use App\Services\Documentations\Interfaces\DocumentationsInterface;
use App\Services\Documentations\Repositories\DocumentationsRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentationsInterface::class, DocumentationsRepository::class);
    }

    public function boot(): void
    {
        $this->registerDynamicGuards();
    }

    /**
     * Register session guards/providers for every actor type. Sanctum still
     * issues API tokens; the session guard is what permits Auth::attempt()
     * during credential login.
     */
    private function registerDynamicGuards(): void
    {
        foreach (config('auth_guards.types', []) as $key => $config) {
            config([
                "auth.providers.{$key}" => [
                    'driver' => 'eloquent',
                    'model' => $config['model'],
                ],
                "auth.guards.{$key}" => [
                    'driver' => 'session',
                    'provider' => $key,
                ],
            ]);
        }
    }
}
