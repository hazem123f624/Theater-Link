<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionTranslation extends Model
{
    protected $table = 'tenant_permission_translations';

    protected $fillable = [
        'permission_id',
        'lang',
        'title',
    ];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
