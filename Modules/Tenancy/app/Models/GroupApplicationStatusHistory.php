<?php

namespace Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admins\Models\Admins;

/**
 * @property int $id
 * @property int $application_id
 * @property int|null $document_id
 * @property string|null $from_status
 * @property string $to_status
 * @property string $action
 * @property int|null $admin_id
 * @property int|null $tenant_member_id
 * @property string|null $note
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read GroupApplication $application
 * @property-read GroupApplicationDocument|null $document
 * @property-read Admins|null $admin
 * @property-read TenantMember|null $member
 */
class GroupApplicationStatusHistory extends Model
{
    protected $table = 'group_application_status_histories';

    protected $fillable = [
        'application_id',
        'document_id',
        'from_status',
        'to_status',
        'action',
        'admin_id',
        'tenant_member_id',
        'note',
        'metadata',
    ];

    protected $casts = [
        'application_id' => 'integer',
        'document_id' => 'integer',
        'admin_id' => 'integer',
        'tenant_member_id' => 'integer',
        'metadata' => 'array',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(GroupApplication::class, 'application_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(GroupApplicationDocument::class, 'document_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admins::class, 'admin_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(TenantMember::class, 'tenant_member_id');
    }
}
