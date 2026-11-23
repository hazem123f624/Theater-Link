<?php

namespace Modules\Bookings\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $booking_id
 * @property string|null $from_status
 * @property string $to_status
 * @property string $action
 * @property string $actor_type
 * @property int|null $actor_id
 * @property string|null $note
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Booking $booking
 */
class BookingStatusHistory extends Model
{
    protected $table = 'booking_status_histories';

    protected $fillable = [
        'booking_id', 'from_status', 'to_status', 'action', 'actor_type', 'actor_id', 'note', 'metadata',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'actor_id' => 'integer',
        'metadata' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
