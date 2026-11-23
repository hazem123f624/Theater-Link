<?php

namespace Modules\Bookings\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Bookings\Console\Commands\ExpireTickets;
use Nwidart\Modules\Traits\PathNamespace;

class BookingsServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Bookings';

    protected string $nameLower = 'bookings';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireTickets::class,
            ]);
        }
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
