<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRoleTranslation extends Model
{
    protected $table = 'tenant_role_translations';

    protected $fillable = [
        'role_id',
        'lang',
        'name',
        'description',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(TenantRole::class, 'role_id');
    }
}
