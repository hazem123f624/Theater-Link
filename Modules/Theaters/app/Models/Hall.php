<?php

namespace Modules\Theaters\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenancy\Models\Tenant;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $description
 * @property int $capacity
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HallSchedule> $schedules
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HallBlock> $blocks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HallTimeSlot> $slots
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Show> $shows
 */
class Hall extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'halls';

    protected $fillable = [
        'tenant_id', 'name', 'description', 'capacity', 'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<HallSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(HallSchedule::class);
    }

    /** @return HasMany<HallBlock, $this> */
    public function blocks(): HasMany
    {
        return $this->hasMany(HallBlock::class);
    }

    /** @return HasMany<HallTimeSlot, $this> */
    public function slots(): HasMany
    {
        return $this->hasMany(HallTimeSlot::class);
    }

    /** @return HasMany<Show, $this> */
    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }
}
