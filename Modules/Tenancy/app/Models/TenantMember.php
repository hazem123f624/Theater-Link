<?php

namespace Modules\Tenancy\Models;

use App\Models\Otp;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $phone_verified_at
 * @property string $password
 * @property bool $is_owner
 * @property bool $is_active
 * @property int $failed_login_attempts
 * @property \Illuminate\Support\Carbon|null $locked_until
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $change_password_token
 * @property \Illuminate\Support\Carbon|null $change_password_expired_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TenantRole> $roles
 */
class TenantMember extends Authenticatable
{
    use HasApiTokens;
    use SoftDeletes;

    protected $table = 'tenant_members';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'password',
        'phone_verified_at',
        'is_owner',
        'is_active',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
        'change_password_token',
        'change_password_expired_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'phone_verified_at' => 'datetime',
        'is_owner' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'change_password_expired_at' => 'datetime',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsToMany<TenantRole, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantRole::class,
            'tenant_member_roles',
            'member_id',
            'role_id'
        )->withPivot('tenant_id');
    }

    /** @return MorphMany<Otp, $this> */
    public function otps(): MorphMany
    {
        return $this->morphMany(Otp::class, 'otpable');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasPermission(string $permission): bool
    {
        $tenantType = $this->currentTenantType();

        if ($this->is_owner) {
            return Permission::query()
                ->where('name', $permission)
                ->where('is_active', true)
                ->when($tenantType, fn ($query) => $query->whereHas(
                    'tenantTypes',
                    fn ($typeQuery) => $typeQuery->where('tenant_type', $tenantType)
                ))
                ->exists();
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query
                ->where('tenant_permissions.name', $permission)
                ->when($tenantType, fn ($permissionQuery) => $permissionQuery->whereHas(
                    'tenantTypes',
                    fn ($typeQuery) => $typeQuery->where('tenant_type', $tenantType)
                )))
            ->exists();
    }

    public function permissionNames(): array
    {
        $tenantType = $this->currentTenantType();

        if ($this->is_owner) {
            return Permission::query()
                ->where('is_active', true)
                ->when($tenantType, fn ($query) => $query->whereHas(
                    'tenantTypes',
                    fn ($typeQuery) => $typeQuery->where('tenant_type', $tenantType)
                ))
                ->pluck('name')
                ->all();
        }

        $names = $this->roles()
            ->with('permissions:id,name')
            ->get()
            ->flatMap(fn (TenantRole $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values();

        return Permission::query()
            ->whereIn('name', $names)
            ->where('is_active', true)
            ->when($tenantType, fn ($query) => $query->whereHas(
                'tenantTypes',
                fn ($typeQuery) => $typeQuery->where('tenant_type', $tenantType)
            ))
            ->pluck('name')
            ->all();
    }

    private function currentTenantType(): ?string
    {
        return $this->relationLoaded('tenant')
            ? $this->tenant->type
            : $this->tenant()->value('type');
    }

    public function setPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['password'] = Hash::isHashed($value) ? $value : Hash::make($value);
    }
}
