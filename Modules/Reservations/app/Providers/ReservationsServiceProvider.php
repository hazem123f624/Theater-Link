<?php

namespace Modules\Reservations\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use Modules\Reservations\Console\Commands\CompleteReservations;
use Modules\Reservations\Console\Commands\ExpireReservationHolds;

class ReservationsServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Reservations';

    protected string $nameLower = 'reservations';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireReservationHolds::class,
                CompleteReservations::class,
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
