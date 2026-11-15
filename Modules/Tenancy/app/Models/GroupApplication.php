<?php

namespace Modules\Tenancy\Models;

use App\Services\MediaSystem\Models\MediaFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $reference
 * @property string $status
 * @property string|null $rejection_reason
 * @property string|null $review_feedback
 * @property string|null $admin_notes
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GroupApplicationDocument> $documents
 * @property-read Tenant $tenant
 */
class GroupApplication extends Model
{
    use SoftDeletes;

    protected $table = 'group_applications';

    protected $fillable = [
        'tenant_id', 'reference', 'status', 'rejection_reason', 'review_feedback',
        'admin_notes', 'submitted_at', 'approved_at', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(GroupApplicationDocument::class, 'application_id');
    }
}
