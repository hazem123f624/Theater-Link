<?php

namespace Modules\Tenancy\Models;

use App\Services\MediaSystem\Models\MediaFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admins\Models\Admins;

/**
 * @property int $id
 * @property int $application_id
 * @property string $document_type
 * @property int|null $media_id
 * @property string|null $original_name
 * @property string $status
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property int|null $uploaded_by
 * @property int $version
 * @property \Illuminate\Support\Carbon|null $resubmitted_at
 * @property string|null $rejection_reason
 * @property string|null $admin_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read GroupApplication $application
 * @property-read MediaFile|null $media
 * @property-read Admins|null $reviewer
 * @property-read TenantMember|null $uploader
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GroupApplicationDocumentVersion> $versions
 */
class GroupApplicationDocument extends Model
{
    protected $table = 'group_application_documents';

    protected $fillable = [
        'application_id', 'document_type', 'media_id', 'original_name',
        'status', 'reviewed_by', 'reviewed_at', 'uploaded_by', 'version',
        'resubmitted_at', 'rejection_reason', 'admin_note',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'media_id' => 'integer',
        'reviewed_by' => 'integer',
        'reviewed_at' => 'datetime',
        'uploaded_by' => 'integer',
        'version' => 'integer',
        'resubmitted_at' => 'datetime',
    ];

    /** @return BelongsTo<GroupApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(GroupApplication::class, 'application_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admins::class, 'reviewed_by');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(TenantMember::class, 'uploaded_by');
    }

    /** @return HasMany<GroupApplicationDocumentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(GroupApplicationDocumentVersion::class, 'document_id')->latest('version');
    }
}
