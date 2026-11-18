<?php

namespace Modules\Theaters\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ticket_tier_id
 * @property string $lang
 * @property string $name
 * @property string|null $description
 * @property-read TicketTier $tier
 */
class TicketTierTranslation extends Model
{
    protected $table = 'ticket_tier_translations';

    protected $fillable = ['ticket_tier_id', 'lang', 'name', 'description'];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(TicketTier::class, 'ticket_tier_id');
    }
}
