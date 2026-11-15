<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
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
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Tenant $tenant
 */
class TheaterProfile extends Model
{
    use SoftDeletes;

    protected $table = 'theater_profiles';

    protected $fillable = [
        'tenant_id', 'name', 'description', 'email', 'phone', 'address',
        'country_id', 'city_id', 'logo_media_id', 'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
