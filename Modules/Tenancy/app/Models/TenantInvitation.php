<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $email
 * @property string|null $name
 * @property int|null $role_id
 * @property int|null $invited_by
 * @property string $token
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Tenant $tenant
 * @property-read TenantRole|null $role
 */
class TenantInvitation extends Model
{
    protected $table = 'tenant_invitations';

    protected $fillable = [
        'tenant_id', 'email', 'name', 'role_id', 'invited_by',
        'token', 'expires_at', 'accepted_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'role_id' => 'integer',
        'invited_by' => 'integer',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(TenantRole::class, 'role_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
