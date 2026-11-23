<?php

namespace Modules\Bookings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenancy\Models\Tenant;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\Show;
use Modules\Users\Models\User;

/**
 * @property int $id
 * @property string $reference
 * @property int $user_id
 * @property int $tenant_id
 * @property int $event_id
 * @property int $show_id
 * @property int $hall_id
 * @property string $status
 * @property string $subtotal
 * @property string $total
 * @property string $currency
 * @property string $idempotency_key
 * @property string $request_hash
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property string|null $cancelled_by_type
 * @property int|null $cancelled_by_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read Tenant $theaterTenant
 * @property-read Event $event
 * @property-read Show $show
 * @property-read Hall $hall
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BookingItem> $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Ticket> $tickets
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BookingStatusHistory> $histories
 */
class Booking extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'reference', 'user_id', 'tenant_id', 'event_id', 'show_id', 'hall_id',
        'status', 'subtotal', 'total', 'currency', 'idempotency_key', 'request_hash',
        'confirmed_at', 'cancelled_at', 'cancellation_reason', 'cancelled_by_type', 'cancelled_by_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'tenant_id' => 'integer',
        'event_id' => 'integer',
        'show_id' => 'integer',
        'hall_id' => 'integer',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cancelled_by_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function theaterTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withoutGlobalScope(Event::CURRENT_TENANT_SCOPE);
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class)->withoutGlobalScope(Show::CURRENT_TENANT_SCOPE);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class)->withoutGlobalScope(Hall::CURRENT_TENANT_SCOPE);
    }

    /** @return HasMany<BookingItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return HasMany<BookingStatusHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->latest();
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
