<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Tenancy\Enums\TenantStatus;
use Modules\Tenancy\Enums\TenantType;

/**
 * @property int $id
 * @property string $ulid
 * @property string $uuid
 * @property string $type
 * @property string $name
 * @property string $subdomain
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TenantMember> $members
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TenantRole> $roles
 * @property-read TheaterProfile|null $theaterProfile
 * @property-read GroupProfile|null $groupProfile
 * @property-read GroupApplication|null $groupApplication
 */
class Tenant extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'uuid',
        'type',
        'name',
        'subdomain',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            $tenant->ulid ??= (string) Str::ulid();
            $tenant->uuid ??= (string) Str::uuid();
        });
    }

    /** @return HasMany<TenantMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(TenantMember::class);
    }

    /** @return HasMany<TenantRole, $this> */
    public function roles(): HasMany
    {
        return $this->hasMany(TenantRole::class);
    }

    /** @return HasOne<TheaterProfile, $this> */
    public function theaterProfile(): HasOne
    {
        return $this->hasOne(TheaterProfile::class);
    }

    /** @return HasOne<GroupProfile, $this> */
    public function groupProfile(): HasOne
    {
        return $this->hasOne(GroupProfile::class);
    }

    /** @return HasOne<GroupApplication, $this> */
    public function groupApplication(): HasOne
    {
        return $this->hasOne(GroupApplication::class);
    }

    /** @return HasMany<\Modules\Theaters\Models\Hall, $this> */
    public function halls(): HasMany
    {
        return $this->hasMany(\Modules\Theaters\Models\Hall::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', TenantStatus::ACTIVE->value);
    }

    public function scopeOfType($query, TenantType|string $type)
    {
        return $query->where('type', $type instanceof TenantType ? $type->value : $type);
    }

    public function isTheater(): bool
    {
        return $this->type === TenantType::THEATER->value;
    }

    public function isGroup(): bool
    {
        return $this->type === TenantType::GROUP->value;
    }

    public function canUseWorkspace(): bool
    {
        return $this->status === TenantStatus::ACTIVE->value;
    }
}
