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
 * @property string|null $previous_status
 * @property string|null $rejection_reason
 * @property string|null $review_feedback
 * @property string|null $admin_notes
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $last_action_at
 * @property \Illuminate\Support\Carbon|null $suspended_at
 * @property string|null $suspension_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GroupApplicationDocument> $documents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GroupApplicationStatusHistory> $histories
 * @property-read Tenant $tenant
 */
class GroupApplication extends Model
{
    use SoftDeletes;

    protected $table = 'group_applications';

    protected $fillable = [
        'tenant_id', 'reference', 'status', 'previous_status', 'rejection_reason', 'review_feedback',
        'admin_notes', 'submitted_at', 'approved_at', 'reviewed_by', 'reviewed_at',
        'last_action_at', 'suspended_at', 'suspension_reason',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'last_action_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return HasMany<GroupApplicationDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(GroupApplicationDocument::class, 'application_id');
    }

    /** @return HasMany<GroupApplicationStatusHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(GroupApplicationStatusHistory::class, 'application_id')->latest();
    }
}
