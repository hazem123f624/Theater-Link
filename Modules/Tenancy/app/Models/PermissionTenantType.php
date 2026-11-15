<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionTenantType extends Model
{
    protected $table = 'tenant_permission_tenant_types';

    protected $fillable = [
        'permission_id',
        'tenant_type',
    ];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
