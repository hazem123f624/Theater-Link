<?php

namespace Modules\Tenancy\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantRolePermission extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_role_permissions';

    protected $fillable = [
        'tenant_id',
        'role_id',
        'permission_id',
    ];
}
