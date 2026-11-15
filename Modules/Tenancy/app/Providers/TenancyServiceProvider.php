<?php

namespace Modules\Tenancy\Providers;

use App\Services\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;
use Modules\Tenancy\Services\TenantProvisioningService;
use Modules\Tenancy\Services\TenantRoleService;
use Nwidart\Modules\Traits\PathNamespace;

class TenancyServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Tenancy';

    protected string $nameLower = 'tenancy';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->scoped(TenantContext::class);
        $this->app->bind(TenantRoleService::class);
        $this->app->bind(TenantProvisioningService::class);
    }

    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
    }

    public function registerConfig(): void
    {
        $this->mergeConfigFrom(module_path($this->name, 'config/config.php'), $this->nameLower);
    }
}
