<?php

namespace Modules\Tenancy\Services;

use App\Services\ActivityLog\ActivityLogService;
use App\Services\MediaSystem\MediaService;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admins\Models\Admins;
use Modules\Tenancy\Enums\GroupApplicationStatus;
use Modules\Tenancy\Enums\GroupDocumentStatus;
use Modules\Tenancy\Enums\TenantStatus;
use Modules\Tenancy\Models\GroupApplication;
use Modules\Tenancy\Models\GroupApplicationDocument;
use Modules\Tenancy\Models\GroupApplicationDocumentVersion;
use Modules\Tenancy\Models\GroupApplicationStatusHistory;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;

class GroupApplicationReviewService
{
    public function __construct(
        private TenantProvisioningService $provisioning,
        private NotificationService $notifications,
        private MediaService $media,
    ) {}

    public function acceptDocument(GroupApplication $application, GroupApplicationDocument $document, Admins $admin): GroupApplication
    {
        return $this->documentAction($application, $document, $admin, GroupDocumentStatus::ACCEPTED, null, 'document_accepted');
    }

    public function rejectDocument(GroupApplication $application, GroupApplicationDocument $document, Admins $admin, string $reason): GroupApplication
    {
        return $this->documentAction($application, $document, $admin, GroupDocumentStatus::REJECTED, $reason, 'document_rejected');
    }

    public function requestDocumentResubmission(GroupApplication $application, GroupApplicationDocument $document, Admins $admin, string $feedback): GroupApplication
    {
        return $this->documentAction($application, $document, $admin, GroupDocumentStatus::REQUIRES_RESUBMISSION, $feedback, 'document_resubmission_requested');
    }

    public function approve(GroupApplication $application, Admins $admin): GroupApplication
    {
        return DB::transaction(function () use ($application, $admin): GroupApplication {
            $application = GroupApplication::query()->lockForUpdate()->findOrFail($application->id);
            $application->load('documents', 'tenant.members');

            if ($application->status === GroupApplicationStatus::APPROVED->value) {
                return $application;
            }

            if (! in_array($application->status, [
                GroupApplicationStatus::PENDING_REVIEW->value,
                GroupApplicationStatus::UNDER_REVIEW->value,
                GroupApplicationStatus::READY_FOR_REVIEW->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'application' => [__('tenancy::messages.application_not_ready_for_approval')],
                ]);
            }

            $blockers = $this->approvalBlockers($application);

            if ($blockers !== []) {
                throw ValidationException::withMessages([
                    'application' => $blockers,
                ]);
            }

            $from = $application->status;
            $this->provisioning->approve($application->tenant);
            $application->update([
                'status' => GroupApplicationStatus::APPROVED->value,
                'previous_status' => null,
                'approved_at' => now(),
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'last_action_at' => now(),
            ]);

            $this->history($application, 'approved', $from, $application->status, null, $admin);
            $this->audit($application, 'group_application.approved', $admin, ['from' => $from, 'to' => $application->status]);
            $this->notifications->notifyGroupOwner(
                $application,
                'application_approved',
                __('tenancy::messages.notification_application_approved_title'),
                __('tenancy::messages.notification_application_approved_body', ['reference' => $application->reference]),
                ['application_id' => $application->id, 'status' => $application->status],
            );

            return $application->fresh(['documents', 'histories', 'tenant']);
        });
    }

    public function reject(GroupApplication $application, Admins $admin, string $reason, ?string $feedback = null): GroupApplication
    {
        return DB::transaction(function () use ($application, $admin, $reason, $feedback): GroupApplication {
            $application = GroupApplication::query()->lockForUpdate()->findOrFail($application->id);

            if ($application->status === GroupApplicationStatus::APPROVED->value) {
                throw ValidationException::withMessages([
                    'application' => [__('tenancy::messages.application_cannot_be_rejected')],
                ]);
            }

            if ($application->status === GroupApplicationStatus::REJECTED->value) {
                return $application;
            }

            $from = $application->status;
            $application->update([
                'previous_status' => $from,
                'status' => GroupApplicationStatus::REJECTED->value,
                'rejection_reason' => $reason,
                'review_feedback' => $feedback,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'last_action_at' => now(),
            ]);

            $this->history($application, 'rejected', $from, $application->status, null, $admin, $reason);
            $this->audit($application, 'group_application.rejected', $admin, ['from' => $from, 'to' => $application->status, 'reason' => $reason]);
            $this->notifications->notifyGroupOwner(
                $application,
                'application_rejected',
                __('tenancy::messages.notification_application_rejected_title'),
                __('tenancy::messages.notification_application_rejected_body', ['reference' => $application->reference]),
                ['application_id' => $application->id, 'status' => $application->status],
            );

            return $application->fresh(['documents', 'histories', 'tenant']);
        });
    }

    public function reopen(GroupApplication $application, Admins $admin): GroupApplication
    {
        return DB::transaction(function () use ($application, $admin): GroupApplication {
            $application = GroupApplication::query()->lockForUpdate()->findOrFail($application->id);

            if ($application->status !== GroupApplicationStatus::REJECTED->value) {
                throw ValidationException::withMessages([
                    'application' => [__('tenancy::messages.only_rejected_can_be_reopened')],
                ]);
            }

            $from = $application->status;
            $to = in_array($application->previous_status, [
                GroupApplicationStatus::PENDING_REVIEW->value,
                GroupApplicationStatus::UNDER_REVIEW->value,
                GroupApplicationStatus::REQUIRES_RESUBMISSION->value,
                GroupApplicationStatus::READY_FOR_REVIEW->value,
            ], true)
                ? $application->previous_status
                : GroupApplicationStatus::PENDING_REVIEW->value;

            $application->update([
                'status' => $to,
                'previous_status' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'last_action_at' => now(),
            ]);

            $this->history($application, 'reopened', $from, $to, null, $admin);
            $this->audit($application, 'group_application.reopened', $admin, ['from' => $from, 'to' => $to]);
            $this->notifications->notifyGroupOwner(
                $application,
                'application_reopened',
                __('tenancy::messages.notification_application_reopened_title'),
                __('tenancy::messages.notification_application_reopened_body', ['reference' => $application->reference]),
                ['application_id' => $application->id, 'status' => $to],
            );

            return $application->fresh(['documents', 'histories', 'tenant']);
        });
    }

    public function addNote(GroupApplication $application, Admins $admin, string $note): GroupApplication
    {
        return DB::transaction(function () use ($application, $admin, $note): GroupApplication {
            $application = GroupApplication::query()->lockForUpdate()->findOrFail($application->id);
            $entry = '['.now()->format('Y-m-d H:i').'] '.$note;
            $application->update([
                'admin_notes' => trim(($application->admin_notes ? $application->admin_notes."\n\n" : '').$entry),
                'last_action_at' => now(),
            ]);

            $this->history($application, 'note_added', $application->status, $application->status, null, $admin, $note);
            $this->audit($application, 'group_application.note_added', $admin, ['note' => $note]);

            return $application->fresh(['documents', 'histories', 'tenant']);
        });
    }

    public function resubmitDocument(
        GroupApplication $application,
        GroupApplicationDocument $document,
        TenantMember $member,
        UploadedFile $file
    ): GroupApplication {
        return DB::transaction(function () use ($application, $document, $member, $file): GroupApplication {
            $application = GroupApplication::query()->lockForUpdate()->findOrFail($application->id);
            $document = $application->documents()->lockForUpdate()->findOrFail($document->id);

            if ($application->tenant_id !== $member->tenant_id) {
                abort(404, __('messages.resource_not_found'));
            }

            if (in_array($application->status, [
                GroupApplicationStatus::APPROVED->value,
                GroupApplicationStatus::REJECTED->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'document' => [__('tenancy::messages.application_not_open_for_resubmission')],
                ]);
            }

            if (! in_array($document->status, [
                GroupDocumentStatus::REJECTED->value,
                GroupDocumentStatus::REQUIRES_RESUBMISSION->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'document' => [__('tenancy::messages.document_not_open_for_resubmission')],
                ]);
            }

            GroupApplicationDocumentVersion::create([
                'document_id' => $document->id,
                'media_id' => $document->media_id,
                'original_name' => $document->original_name,
                'version' => $document->version,
                'uploaded_by' => $document->uploaded_by,
            ]);

            $media = $this->media
                ->useDisk(config('filesystems.default'))
                ->withVisibility('private')
                ->withCategory('group_permit')
                ->upload($file, $application, 'permit', "group_applications/{$application->id}/resubmissions");

            $from = $application->status;
            $document->update([
                'media_id' => $media->id,
                'original_name' => $media->original_name,
                'status' => GroupDocumentStatus::PENDING->value,
                'rejection_reason' => null,
                'admin_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'uploaded_by' => $member->id,
                'version' => $document->version + 1,
                'resubmitted_at' => now(),
            ]);

            $application->update([
                'status' => GroupApplicationStatus::PENDING_REVIEW->value,
                'last_action_at' => now(),
            ]);

            $this->history($application, 'document_resubmitted', $from, $application->status, $document, null, null, $member);
            $this->audit($application, 'group_application.document_resubmitted', $member, ['document_type' => $document->document_type, 'version' => $document->version]);
            $this->notifications->notifyAdmins(
                'application_resubmitted',
                __('tenancy::messages.notification_application_resubmitted_title'),
                __('tenancy::messages.notification_application_resubmitted_body', ['reference' => $application->reference]),
                ['application_id' => $application->id, 'status' => $application->status],
            );

            return $application->fresh(['documents', 'histories', 'tenant']);
        });
    }

    public function suspend(GroupApplication $application, Admins $admin, string $reason): GroupApplication
    {
        return DB::transaction(function () use ($application, $admin, $reason): GroupApplication {
            $application = GroupApplication::query()->lockForUpdate()->findOrFail($application->id);

            if ($application->status !== GroupApplicationStatus::APPROVED->value) {
                throw ValidationException::withMessages([
                    'application' => [__('tenancy::messages.only_approved_can_be_suspended')],
                ]);
            }

            Tenant::query()->lockForUpdate()->findOrFail($application->tenant_id)->update(['status' => TenantStatus::SUSPENDED->value]);
            $application->update([
                'suspended_at' => now(),
                'suspension_reason' => $reason,
                'last_action_at' => now(),
            ]);

            $this->history($application, 'workspace_suspended', $application->status, $application->status, null, $admin, $reason);
            $this->audit($application, 'group_application.suspended', $admin, ['reason' => $reason]);
            $this->notifications->notifyGroupOwner(
                $application,
                'workspace_suspended',
                __('tenancy::messages.notification_workspace_suspended_title'),
                __('tenancy::messages.notification_workspace_suspended_body', ['reason' => $reason]),
                ['application_id' => $application->id],
            );

            return $application->fresh(['documents', 'histories', 'tenant']);
        });
    }

    public function reactivate(GroupApplication $application, Admins $admin): GroupApplication
    {
        return DB::transaction(function () use ($application, $admin): GroupApplication {
            $application = GroupApplication::query()->lockForUpdate()->findOrFail($application->id);
            Tenant::query()->lockForUpdate()->findOrFail($application->tenant_id)->update(['status' => TenantStatus::ACTIVE->value]);
            $application->update([
                'suspended_at' => null,
                'suspension_reason' => null,
                'last_action_at' => now(),
            ]);

            $this->history($application, 'workspace_reactivated', $application->status, $application->status, null, $admin);
            $this->audit($application, 'group_application.reactivated', $admin);
            $this->notifications->notifyGroupOwner(
                $application,
                'workspace_reactivated',
                __('tenancy::messages.notification_workspace_reactivated_title'),
                __('tenancy::messages.notification_workspace_reactivated_body'),
                ['application_id' => $application->id],
            );

            return $application->fresh(['documents', 'histories', 'tenant']);
        });
    }

    /**
     * @return array<int, string>
     */
    public function approvalBlockers(GroupApplication $application): array
    {
        $application->loadMissing('documents', 'tenant.members');
        $required = config('tenancy.group_application.required_documents', ['license']);
        $documents = $application->documents;
        $blockers = [];

        $missing = array_diff($required, $documents->pluck('document_type')->all());
        foreach ($missing as $type) {
            $blockers[] = __('tenancy::messages.required_document_missing', ['type' => $type]);
        }

        foreach ($documents as $document) {
            if ($document->status !== GroupDocumentStatus::ACCEPTED->value) {
                $blockers[] = __('tenancy::messages.document_not_accepted', ['type' => $document->document_type]);
            }
        }

        $owner = $application->tenant->members->firstWhere('is_owner', true);
        if (! $owner || ! $owner->phone_verified_at) {
            $blockers[] = __('tenancy::messages.owner_phone_not_verified');
        }

        return array_values(array_unique($blockers));
    }

    private function documentAction(
        GroupApplication $application,
        GroupApplicationDocument $document,
        Admins $admin,
        GroupDocumentStatus $status,
        ?string $feedback,
        string $action
    ): GroupApplication {
        return DB::transaction(function () use ($application, $document, $admin, $status, $feedback, $action): GroupApplication {
            $application = GroupApplication::query()->lockForUpdate()->findOrFail($application->id);
            $document = $application->documents()->lockForUpdate()->findOrFail($document->id);

            if (in_array($application->status, [
                GroupApplicationStatus::APPROVED->value,
                GroupApplicationStatus::REJECTED->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'application' => [__('tenancy::messages.application_not_open_for_review')],
                ]);
            }

            if ($document->status !== GroupDocumentStatus::PENDING->value
                && ! ($status === GroupDocumentStatus::REQUIRES_RESUBMISSION
                    && $document->status === GroupDocumentStatus::REJECTED->value)) {
                throw ValidationException::withMessages([
                    'document' => [__('tenancy::messages.document_already_reviewed')],
                ]);
            }

            $from = $application->status;
            $document->update([
                'status' => $status->value,
                'rejection_reason' => $status === GroupDocumentStatus::REJECTED ? $feedback : null,
                'admin_note' => $feedback,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $application->load('documents', 'tenant.members');
            $to = $this->recomputeStatus($application);
            $application->update([
                'status' => $to,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'last_action_at' => now(),
            ]);

            $this->history($application, $action, $from, $to, $document, $admin, $feedback);
            $this->audit($application, 'group_application.'.$action, $admin, [
                'document_type' => $document->document_type,
                'document_status' => $status->value,
                'from' => $from,
                'to' => $to,
            ]);

            $notificationType = match ($action) {
                'document_accepted' => 'document_accepted',
                'document_rejected' => 'document_rejected',
                default => 'document_resubmission_requested',
            };
            $this->notifications->notifyGroupOwner(
                $application,
                $notificationType,
                __('tenancy::messages.notification_'.$notificationType.'_title'),
                __('tenancy::messages.notification_'.$notificationType.'_body', [
                    'reference' => $application->reference,
                    'type' => $document->document_type,
                ]),
                ['application_id' => $application->id, 'document_id' => $document->id, 'status' => $to],
            );

            return $application->fresh(['documents', 'histories', 'tenant']);
        });
    }

    private function recomputeStatus(GroupApplication $application): string
    {
        $required = config('tenancy.group_application.required_documents', ['license']);
        $documents = $application->documents;
        $missing = array_diff($required, $documents->pluck('document_type')->all());

        if ($missing !== []) {
            return GroupApplicationStatus::PENDING_REVIEW->value;
        }

        if ($documents->contains(fn (GroupApplicationDocument $document): bool => in_array($document->status, [
            GroupDocumentStatus::REJECTED->value,
            GroupDocumentStatus::REQUIRES_RESUBMISSION->value,
        ], true))) {
            return GroupApplicationStatus::REQUIRES_RESUBMISSION->value;
        }

        if ($documents->every(fn (GroupApplicationDocument $document): bool => $document->status === GroupDocumentStatus::ACCEPTED->value)) {
            return GroupApplicationStatus::READY_FOR_REVIEW->value;
        }

        if ($documents->contains(fn (GroupApplicationDocument $document): bool => $document->reviewed_at !== null || $document->status === GroupDocumentStatus::ACCEPTED->value)) {
            return GroupApplicationStatus::UNDER_REVIEW->value;
        }

        return GroupApplicationStatus::PENDING_REVIEW->value;
    }

    private function history(
        GroupApplication $application,
        string $action,
        ?string $from,
        string $to,
        ?GroupApplicationDocument $document = null,
        ?Admins $admin = null,
        ?string $note = null,
        ?TenantMember $member = null
    ): void {
        GroupApplicationStatusHistory::create([
            'application_id' => $application->id,
            'document_id' => $document?->id,
            'from_status' => $from,
            'to_status' => $to,
            'action' => $action,
            'admin_id' => $admin?->id,
            'tenant_member_id' => $member?->id,
            'note' => $note,
        ]);
    }

    private function audit(GroupApplication $application, string $action, Admins|TenantMember $causer, array $properties = []): void
    {
        ActivityLogService::log(
            action: $action,
            description: __('tenancy::messages.activity_group_application_review', ['action' => $action]),
            subject: $application,
            properties: $properties,
            causer: $causer,
            type: $causer instanceof Admins ? 'admin' : 'user',
        );
    }
}
