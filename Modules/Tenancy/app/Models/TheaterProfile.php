<?php

namespace Modules\Tenancy\Models;

use App\Services\MediaSystem\Models\MediaFile;
use Illuminate\Database\Eloquent\Model;
use Modules\Settings\Models\Settings;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $description
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property int|null $country_id
 * @property int|null $city_id
 * @property int|null $logo_media_id
 * @property int|null $cover_media_id
 * @property string $timezone
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read MediaFile|null $logo
 * @property-read MediaFile|null $cover
 * @property-read Settings|null $country
 * @property-read Settings|null $city
 */
class TheaterProfile extends Model
{
    use SoftDeletes;

    protected $table = 'theater_profiles';

    protected $fillable = [
        'tenant_id', 'name', 'description', 'email', 'phone', 'address',
        'country_id', 'city_id', 'logo_media_id', 'cover_media_id', 'timezone', 'is_active',
    ];

    /** @var array<string, string> */
    protected $attributes = [
        'timezone' => 'Asia/Riyadh',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'logo_media_id' => 'integer',
        'cover_media_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'logo_media_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'cover_media_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Settings::class, 'country_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(Settings::class, 'city_id');
    }
}
