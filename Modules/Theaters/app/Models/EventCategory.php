<?php

namespace Modules\Theaters\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventCategoryTranslation> $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Event> $events
 */
class EventCategory extends Model
{
    protected $table = 'event_categories';

    protected $fillable = ['slug', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** @return HasMany<EventCategoryTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(EventCategoryTranslation::class, 'category_id');
    }

    /** @return HasMany<Event, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'category_id');
    }
}
