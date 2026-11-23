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
 * @property int|null $hall_schedule_id
 * @property int|null $block_id
 * @property int|null $show_id
 * @property int|null $reservation_id
 * @property \Illuminate\Support\Carbon $starts_at
 * @property \Illuminate\Support\Carbon $ends_at
 * @property string $status
 * @property string $price
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Hall $hall
 * @property-read HallSchedule|null $schedule
 * @property-read HallBlock|null $block
 * @property-read Show|null $show
 */
class HallTimeSlot extends Model
{
    use BelongsToTenant;

    protected $table = 'hall_time_slots';

    protected $fillable = [
        'tenant_id', 'hall_id', 'hall_schedule_id', 'block_id', 'show_id', 'reservation_id',
        'starts_at', 'ends_at', 'status', 'price', 'currency',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'hall_id' => 'integer',
        'hall_schedule_id' => 'integer',
        'block_id' => 'integer',
        'show_id' => 'integer',
        'reservation_id' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    /** @return BelongsTo<Hall, $this> */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(HallSchedule::class, 'hall_schedule_id');
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(HallBlock::class, 'block_id');
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class, 'show_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(\Modules\Reservations\Models\Reservation::class, 'reservation_id');
    }
}
