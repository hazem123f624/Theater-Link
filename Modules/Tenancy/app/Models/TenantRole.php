<?php

namespace Modules\Tenancy\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $template_key
 * @property bool $is_system
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 */
class TenantRole extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'tenant_roles';

    protected $fillable = [
        'tenant_id',
        'name',
        'template_key',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(TenantRoleTranslation::class, 'role_id');
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'tenant_role_permissions',
            'role_id',
            'permission_id'
        )->withPivot('tenant_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantMember::class,
            'tenant_member_roles',
            'role_id',
            'member_id'
        )->withPivot('tenant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
