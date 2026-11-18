<?php

namespace Modules\Theaters\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $event_id
 * @property string $lang
 * @property string $title
 * @property string|null $description
 * @property-read Event $event
 */
class EventTranslation extends Model
{
    protected $table = 'event_translations';

    protected $fillable = ['event_id', 'lang', 'title', 'description'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
