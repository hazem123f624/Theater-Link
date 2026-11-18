<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $permission_id
 * @property string $tenant_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Permission $permission
 */
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
