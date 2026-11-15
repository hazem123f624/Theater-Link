<?php

namespace Modules\Tenancy\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tenancy\Models\Permission;
use Modules\Tenancy\Models\PermissionTenantType;
use Modules\Tenancy\Models\PermissionTranslation;

class TenancyDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (config('tenancy.permissions', []) as $definition) {
                $permission = Permission::updateOrCreate(
                    ['name' => $definition['name']],
                    [
                        'module' => $definition['module'],
                        'action' => $definition['action'],
                        'is_active' => true,
                    ]
                );

                foreach ($definition['title'] as $lang => $title) {
                    PermissionTranslation::updateOrCreate(
                        ['permission_id' => $permission->id, 'lang' => $lang],
                        ['title' => $title]
                    );
                }

                foreach ($definition['types'] as $tenantType) {
                    PermissionTenantType::updateOrCreate(
                        ['permission_id' => $permission->id, 'tenant_type' => $tenantType],
                        []
                    );
                }
            }
        });
    }
}
