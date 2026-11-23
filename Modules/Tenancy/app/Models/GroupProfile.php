<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\MediaSystem\Models\MediaFile;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $description
 * @property string|null $contact_person_name
 * @property string|null $email
 * @property string|null $phone
 * @property int|null $logo_media_id
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read MediaFile|null $logo
 */
class GroupProfile extends Model
{
    use SoftDeletes;

    protected $table = 'group_profiles';

    protected $fillable = [
        'tenant_id', 'name', 'description', 'contact_person_name', 'email',
        'phone', 'logo_media_id', 'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
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
}
