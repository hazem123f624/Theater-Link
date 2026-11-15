<?php

namespace Modules\Tenancy\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantMemberRole extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_member_roles';

    protected $fillable = [
        'tenant_id',
        'member_id',
        'role_id',
    ];
}
