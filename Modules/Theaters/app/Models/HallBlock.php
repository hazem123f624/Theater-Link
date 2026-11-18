<?php

namespace Modules\Theaters\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $hall_id
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon $ends_at
 * @property string|null $reason
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Hall $hall
 * @property-read TenantMember|null $creator
 */
class HallBlock extends Model
{
    use BelongsToTenant;

    protected $table = 'hall_blocks';

    protected $fillable = [
        'tenant_id', 'hall_id', 'starts_at', 'ends_at', 'reason', 'created_by',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'hall_id' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'created_by' => 'integer',
    ];

    /** @return BelongsTo<Hall, $this> */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantMember::class, 'created_by');
    }
}
