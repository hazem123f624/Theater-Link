<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    protected $table = 'tenant_permissions';

    protected $fillable = [
        'name',
        'module',
        'action',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(PermissionTranslation::class);
    }

    public function tenantTypes(): HasMany
    {
        return $this->hasMany(PermissionTenantType::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantRole::class,
            'tenant_role_permissions',
            'permission_id',
            'role_id'
        )->withPivot('tenant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function allowsTenantType(string $tenantType): bool
    {
        return $this->tenantTypes()->where('tenant_type', $tenantType)->exists();
    }
}
