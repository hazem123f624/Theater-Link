<?php

namespace App\Services\Tenancy;

use App\Services\MediaSystem\MediaService;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Tenancy\Enums\GroupApplicationStatus;
use Modules\Tenancy\Enums\GroupDocumentStatus;
use Modules\Tenancy\Models\GroupApplication;
use Modules\Tenancy\Models\GroupApplicationDocument;
use Modules\Tenancy\Models\GroupApplicationStatusHistory;
use Modules\Tenancy\Models\GroupProfile;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TheaterProfile;
use Modules\Tenancy\Services\TenantProvisioningService;

class TenantRegistrationService
{
    public function __construct(
        private TenantProvisioningService $provisioning,
        private MediaService $media,
        private NotificationService $notifications,
    ) {}

    public function temporaryDirectory(string $phone, string $type): string
    {
        return 'tenant_otp/'.substr(hash('sha256', $type.':'.$phone), 0, 20);
    }

    /**
     * @return array{path: string, name: string, mime: string}
     */
    public function storeTemporaryFile(UploadedFile $file, string $phone, string $type): array
    {
        $directory = $this->temporaryDirectory($phone, $type);
        Storage::disk('local')->deleteDirectory($directory);

        return [
            'path' => $file->store($directory, 'local'),
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
        ];
    }

    /**
     * @param  array{path: string, name: string, mime: string}  $metadata
     */
    public function rehydrateTemporaryFile(array $metadata): UploadedFile
    {
        return new UploadedFile(
            Storage::disk('local')->path($metadata['path']),
            $metadata['name'],
            $metadata['mime'],
            null,
            true
        );
    }

    public function cleanupTemporaryFiles(string $phone, string $type): void
    {
        Storage::disk('local')->deleteDirectory($this->temporaryDirectory($phone, $type));
    }

    /**
     * @param  array{name: string, subdomain: string, owner_name: string, email: string, phone: string, password: string, address?: string|null, country_id?: int|null, city_id?: int|null}  $data
     */
    public function createTheater(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $tenant = $this->provisioning->provision(
                ['type' => 'theater', 'name' => $data['name'], 'subdomain' => $data['subdomain']],
                [
                    'name' => $data['owner_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => $data['password'],
                ]
            );

            TheaterProfile::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'] ?? null,
                'country_id' => $data['country_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
            ]);

            return $tenant->fresh(['members', 'roles']);
        });
    }

    /**
     * @param  array{name: string, subdomain: string, contact_person_name: string, email: string, phone: string, password: string, description?: string|null}  $data
     * @return array{tenant: Tenant, application: GroupApplication}
     */
    public function createGroup(array $data, ?UploadedFile $permit = null): array
    {
        return DB::transaction(function () use ($data, $permit): array {
            $tenant = $this->provisioning->provision(
                ['type' => 'group', 'name' => $data['name'], 'subdomain' => $data['subdomain']],
                [
                    'name' => $data['contact_person_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => $data['password'],
                ]
            );

            GroupProfile::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'contact_person_name' => $data['contact_person_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);

            $application = GroupApplication::create([
                'tenant_id' => $tenant->id,
                'reference' => $this->applicationReference(),
                'status' => GroupApplicationStatus::PENDING_REVIEW->value,
                'submitted_at' => now(),
            ]);

            if ($permit) {
                $this->storePermit($application, $permit);
            }

            GroupApplicationStatusHistory::create([
                'application_id' => $application->id,
                'to_status' => $application->status,
                'action' => 'submitted',
                'note' => __('tenancy::messages.application_submitted_history'),
            ]);

            $this->notifications->notifyAdmins(
                'application_submitted',
                __('tenancy::messages.notification_application_submitted_title'),
                __('tenancy::messages.notification_application_submitted_body', ['reference' => $application->reference]),
                ['application_id' => $application->id, 'status' => $application->status],
            );

            return [
                'tenant' => $tenant->fresh(['members', 'roles']),
                'application' => $application->fresh('documents'),
            ];
        });
    }

    public function storePermit(GroupApplication $application, UploadedFile $permit): GroupApplicationDocument
    {
        $media = $this->media
            ->useDisk(config('filesystems.default'))
            ->withVisibility('private')
            ->withCategory('group_permit')
            ->upload(
                $permit,
                $application,
                'permit',
                "group_applications/{$application->id}"
            );

        $application->loadMissing('tenant.members');
        $ownerId = $application->tenant->members->firstWhere('is_owner', true)?->id;

        return GroupApplicationDocument::updateOrCreate(
            [
                'application_id' => $application->id,
                'document_type' => 'license',
            ],
            [
                'media_id' => $media->id,
                'original_name' => $media->original_name,
                'status' => GroupDocumentStatus::PENDING->value,
                'rejection_reason' => null,
                'uploaded_by' => $ownerId,
                'version' => 1,
            ]
        );
    }

    public function applicationReference(): string
    {
        $prefix = 'GRP-'.now()->year.'-';
        $sequence = GroupApplication::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (GroupApplication::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public function subdomainAvailable(string $subdomain): bool
    {
        return ! Tenant::withTrashed()->where('subdomain', Str::lower($subdomain))->exists();
    }
}
