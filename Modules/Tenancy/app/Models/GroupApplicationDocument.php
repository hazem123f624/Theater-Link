<?php

namespace Modules\Tenancy\Models;

use App\Services\MediaSystem\Models\MediaFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $application_id
 * @property string $document_type
 * @property int|null $media_id
 * @property string|null $original_name
 * @property string $status
 * @property string|null $rejection_reason
 * @property string|null $admin_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read GroupApplication $application
 * @property-read MediaFile|null $media
 */
class GroupApplicationDocument extends Model
{
    protected $table = 'group_application_documents';

    protected $fillable = [
        'application_id', 'document_type', 'media_id', 'original_name',
        'status', 'rejection_reason', 'admin_note',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'media_id' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(GroupApplication::class, 'application_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_id');
    }
}
