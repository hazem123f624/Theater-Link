<?php

namespace Modules\Theaters\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Models\Tenant;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $hall_id
 * @property int $day_of_week
 * @property string $starts_at
 * @property string $ends_at
 * @property int $slot_duration_minutes
 * @property string $price
 * @property string $currency
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Hall $hall
 * @property-read Tenant $tenant
 */
class HallSchedule extends Model
{
    use BelongsToTenant;

    protected $table = 'hall_schedules';

    protected $fillable = [
        'tenant_id', 'hall_id', 'day_of_week', 'starts_at', 'ends_at',
        'slot_duration_minutes', 'price', 'currency', 'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'hall_id' => 'integer',
        'day_of_week' => 'integer',
        'slot_duration_minutes' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Hall, $this> */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
