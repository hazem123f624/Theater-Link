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
 * @property int $event_id
 * @property int $hall_id
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon $ends_at
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property-read Tenant $tenant
 * @property-read Event $event
 * @property-read Hall $hall
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TicketTier> $tiers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HallTimeSlot> $slots
 */
class Show extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'shows';

    protected $fillable = [
        'tenant_id', 'event_id', 'hall_id', 'starts_at', 'ends_at', 'status', 'cancelled_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'event_id' => 'integer',
        'hall_id' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Hall, $this> */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    /** @return HasMany<TicketTier, $this> */
    public function tiers(): HasMany
    {
        return $this->hasMany(TicketTier::class);
    }

    /** @return HasMany<HallTimeSlot, $this> */
    public function slots(): HasMany
    {
        return $this->hasMany(HallTimeSlot::class);
    }
}
