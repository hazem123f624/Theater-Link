<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $module
 * @property string|null $action
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PermissionTranslation> $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PermissionTenantType> $tenantTypes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TenantRole> $roles
 */
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

    /** @return HasMany<PermissionTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(PermissionTranslation::class);
    }

    /** @return HasMany<PermissionTenantType, $this> */
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
