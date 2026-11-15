<?php

namespace App\Traits;

use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    public const CURRENT_TENANT_SCOPE = 'current_tenant';

    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(self::CURRENT_TENANT_SCOPE, function (Builder $builder): void {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($builder->getModel()->qualifyColumn('tenant_id'), $tenantId);
        });
    }
}
