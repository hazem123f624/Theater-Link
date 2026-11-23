<?php

namespace Modules\Reservations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\HallTimeSlot;

/**
 * @property int $id
 * @property string $reference
 * @property int $theater_tenant_id
 * @property int $group_tenant_id
 * @property int $hall_id
 * @property int $hall_time_slot_id
 * @property int|null $requested_by
 * @property string $status
 * @property string $price
 * @property string $currency
 * @property \Illuminate\Support\Carbon $hold_expires_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $rejection_reason
 * @property string|null $cancellation_reason
 * @property string $idempotency_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Tenant $theaterTenant
 * @property-read Tenant $groupTenant
 * @property-read Hall|null $hall
 * @property-read HallTimeSlot|null $slot
 * @property-read TenantMember|null $requester
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ReservationStatusHistory> $histories
 */
class Reservation extends Model
{
    use SoftDeletes;

    protected $table = 'reservations';

    protected $fillable = [
        'reference', 'theater_tenant_id', 'group_tenant_id', 'hall_id',
        'hall_time_slot_id', 'requested_by', 'status', 'price', 'currency',
        'hold_expires_at', 'approved_at', 'rejected_at', 'cancelled_at',
        'completed_at', 'rejection_reason', 'cancellation_reason', 'idempotency_key',
    ];

    protected $casts = [
        'theater_tenant_id' => 'integer',
        'group_tenant_id' => 'integer',
        'hall_id' => 'integer',
        'hall_time_slot_id' => 'integer',
        'requested_by' => 'integer',
        'price' => 'decimal:2',
        'hold_expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function theaterTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'theater_tenant_id');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function groupTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'group_tenant_id');
    }

    /** @return BelongsTo<Hall, $this> */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class)->withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE);
    }

    /** @return BelongsTo<HallTimeSlot, $this> */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(HallTimeSlot::class, 'hall_time_slot_id')->withoutGlobalScope(HallTimeSlot::CURRENT_TENANT_SCOPE);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(TenantMember::class, 'requested_by');
    }

    /** @return HasMany<ReservationStatusHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(ReservationStatusHistory::class)->latest();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
