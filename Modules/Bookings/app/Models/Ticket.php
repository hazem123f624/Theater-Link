<?php

namespace Modules\Bookings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Models\Tenant;
use Modules\Theaters\Models\Show;
use Modules\Theaters\Models\TicketTier;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $booking_id
 * @property int $booking_item_id
 * @property int $ticket_tier_id
 * @property int $show_id
 * @property int $sequence
 * @property string $ticket_number
 * @property string $qr_token
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $expired_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Booking $booking
 * @property-read BookingItem $bookingItem
 * @property-read TicketTier $tier
 * @property-read Show $show
 * @property-read Tenant $theaterTenant
 */
class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'tenant_id', 'booking_id', 'booking_item_id', 'ticket_tier_id', 'show_id',
        'sequence', 'ticket_number', 'qr_token', 'status', 'used_at', 'cancelled_at', 'expired_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'booking_id' => 'integer',
        'booking_item_id' => 'integer',
        'ticket_tier_id' => 'integer',
        'show_id' => 'integer',
        'sequence' => 'integer',
        'used_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class, 'ticket_tier_id')->withoutGlobalScope(TicketTier::CURRENT_TENANT_SCOPE);
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class)->withoutGlobalScope(Show::CURRENT_TENANT_SCOPE);
    }

    public function theaterTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }
}
