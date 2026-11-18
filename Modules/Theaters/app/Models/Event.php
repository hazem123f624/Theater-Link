<?php

namespace Modules\Theaters\Models;

use App\Services\MediaSystem\Models\MediaFile;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenancy\Models\Tenant;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $category_id
 * @property string $slug
 * @property int|null $poster_media_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read EventCategory|null $category
 * @property-read MediaFile|null $poster
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventTranslation> $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Show> $shows
 */
class Event extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'events';

    protected $fillable = [
        'tenant_id', 'category_id', 'slug', 'poster_media_id', 'status',
        'published_at', 'cancelled_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'category_id' => 'integer',
        'poster_media_id' => 'integer',
        'published_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'poster_media_id');
    }

    /** @return HasMany<EventTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(EventTranslation::class);
    }

    /** @return HasMany<Show, $this> */
    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }

    public function translation(string $lang): ?EventTranslation
    {
        return $this->translations->firstWhere('lang', $lang);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && ! $this->cancelled_at;
    }
}
