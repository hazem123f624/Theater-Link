<?php

namespace Modules\Bookings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Theaters\Models\TicketTier;

/**
 * @property int $id
 * @property int $booking_id
 * @property int $ticket_tier_id
 * @property string|null $tier_name_en
 * @property string|null $tier_name_ar
 * @property string $unit_price
 * @property string $currency
 * @property int $quantity
 * @property string $line_total
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Booking $booking
 * @property-read TicketTier $tier
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Ticket> $tickets
 */
class BookingItem extends Model
{
    protected $table = 'booking_items';

    protected $fillable = [
        'booking_id', 'ticket_tier_id', 'tier_name_en', 'tier_name_ar',
        'unit_price', 'currency', 'quantity', 'line_total',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'ticket_tier_id' => 'integer',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'line_total' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class, 'ticket_tier_id')->withoutGlobalScope(TicketTier::CURRENT_TENANT_SCOPE);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
