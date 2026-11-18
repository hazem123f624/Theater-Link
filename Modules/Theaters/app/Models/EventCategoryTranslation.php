<?php

namespace Modules\Theaters\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $category_id
 * @property string $lang
 * @property string $name
 * @property-read EventCategory $category
 */
class EventCategoryTranslation extends Model
{
    protected $table = 'event_category_translations';

    protected $fillable = ['category_id', 'lang', 'name'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }
}
