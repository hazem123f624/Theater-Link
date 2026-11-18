<?php

namespace Modules\Tenancy\Models;

use App\Services\MediaSystem\Models\MediaFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $document_id
 * @property int|null $media_id
 * @property string|null $original_name
 * @property int $version
 * @property int|null $uploaded_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read GroupApplicationDocument $document
 * @property-read MediaFile|null $media
 * @property-read TenantMember|null $uploader
 */
class GroupApplicationDocumentVersion extends Model
{
    protected $table = 'group_application_document_versions';

    protected $fillable = [
        'document_id',
        'media_id',
        'original_name',
        'version',
        'uploaded_by',
    ];

    protected $casts = [
        'document_id' => 'integer',
        'media_id' => 'integer',
        'version' => 'integer',
        'uploaded_by' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(GroupApplicationDocument::class, 'document_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(TenantMember::class, 'uploaded_by');
    }
}
