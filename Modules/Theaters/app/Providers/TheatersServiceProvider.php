<?php

namespace Modules\Theaters\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class TheatersServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Theaters';

    protected string $nameLower = 'theaters';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
    }
}
