<?php

namespace Tests\Feature;

use App\Services\Notifications\Models\AppNotification;
use App\Services\Tenancy\TenantRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Modules\Admins\Database\Seeders\AdminsDatabaseSeeder;
use Modules\Admins\Models\Admins;
use Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder;
use Modules\Tenancy\Enums\GroupApplicationStatus;
use Modules\Tenancy\Models\GroupApplication;
use Modules\Tenancy\Models\GroupApplicationDocument;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantMember;
use Tests\TestCase;

class Phase3ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        Queue::fake();
        Storage::fake('local');
        $this->seed(TenancyDatabaseSeeder::class);
        $this->seed(AdminsDatabaseSeeder::class);
    }

    public function test_admin_can_list_and_view_group_applications(): void
    {
        [$tenant, $application, $document] = $this->makeGroupApplication('phase3-list');
        $token = $this->adminToken();

        $this->withToken($token)->getJson('/api/v1/dashboard/group-applications?search=phase3-list&status=pending_review')
            ->assertOk()
            ->assertJsonPath('data.0.id', $application->id)
            ->assertJsonPath('data.0.documents_count', 1);

        $this->withToken($token)->getJson('/api/v1/dashboard/group-applications/'.$application->id)
            ->assertOk()
            ->assertJsonPath('data.reference', $application->reference)
            ->assertJsonPath('data.owner.email', 'owner@phase3-list.test')
            ->assertJsonPath('data.documents.0.id', $document->id)
            ->assertJsonPath('data.can_approve', false);
    }

    public function test_invalid_application_status_filter_is_rejected(): void
    {
        $this->withToken($this->adminToken())
            ->getJson('/api/v1/dashboard/group-applications?status=not-a-status')
            ->assertStatus(422);
    }

    public function test_accepting_the_required_document_makes_application_ready_and_approval_activates_the_group(): void
    {
        [$tenant, $application, $document] = $this->makeGroupApplication('phase3-approve');
        $token = $this->adminToken();

        $this->withToken($token)->postJson("/api/v1/dashboard/group-applications/{$application->id}/documents/{$document->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', GroupApplicationStatus::READY_FOR_REVIEW->value)
            ->assertJsonPath('data.documents.0.status', 'accepted')
            ->assertJsonPath('data.can_approve', true);

        $this->withToken($token)->postJson("/api/v1/dashboard/group-applications/{$application->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', GroupApplicationStatus::APPROVED->value);

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'active']);
        $this->assertDatabaseHas('group_applications', ['id' => $application->id, 'status' => 'approved']);
    }

    public function test_approval_is_blocked_until_the_required_document_is_accepted(): void
    {
        [$tenant, $application] = $this->makeGroupApplication('phase3-blocked');

        $this->withToken($this->adminToken())
            ->postJson("/api/v1/dashboard/group-applications/{$application->id}/approve")
            ->assertStatus(422);

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'pending_review']);
    }

    public function test_approval_is_blocked_when_the_owner_phone_is_not_verified(): void
    {
        [$tenant, $application, $document] = $this->makeGroupApplication('phase3-phone');
        $owner = $tenant->members()->where('is_owner', true)->firstOrFail();
        $owner->update(['phone_verified_at' => null]);
        $token = $this->adminToken();

        $this->withToken($token)->postJson("/api/v1/dashboard/group-applications/{$application->id}/documents/{$document->id}/accept")->assertOk();

        $this->withToken($token)->postJson("/api/v1/dashboard/group-applications/{$application->id}/approve")
            ->assertStatus(422);
    }

    public function test_rejection_and_reopening_preserve_the_application_history(): void
    {
        [, $application] = $this->makeGroupApplication('phase3-reopen');
        $token = $this->adminToken();

        $this->withToken($token)->postJson("/api/v1/dashboard/group-applications/{$application->id}/reject", [
            'reason' => 'The permit is unreadable.',
            'review_feedback' => 'Please replace the permit.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'The permit is unreadable.');

        $this->withToken($token)->postJson("/api/v1/dashboard/group-applications/{$application->id}/reopen")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_review')
            ->assertJsonPath('data.rejection_reason', 'The permit is unreadable.');

        $this->assertDatabaseHas('group_application_status_histories', [
            'application_id' => $application->id,
            'action' => 'rejected',
        ]);
        $this->assertDatabaseHas('group_application_status_histories', [
            'application_id' => $application->id,
            'action' => 'reopened',
        ]);
    }

    public function test_group_can_resubmit_a_returned_document(): void
    {
        [$tenant, $application, $document] = $this->makeGroupApplication('phase3-resubmit');
        $document->update([
            'status' => 'rejected',
            'rejection_reason' => 'Please upload a clearer permit.',
        ]);
        $application->update(['status' => 'requires_resubmission']);

        $groupToken = $this->postJson('/api/v1/group/login', [
            'subdomain' => 'phase3-resubmit',
            'email' => 'owner@phase3-resubmit.test',
            'password' => 'P@ssw0rd123',
        ])->assertOk()->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$groupToken)
            ->postJson("/api/v1/group/application/documents/{$document->id}/resubmit", [
                'document' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_review')
            ->assertJsonPath('data.documents.0.status', 'pending')
            ->assertJsonPath('data.documents.0.version', 2);

        $this->assertDatabaseHas('group_application_document_versions', [
            'document_id' => $document->id,
            'version' => 1,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$groupToken)
            ->get("/api/v1/group/application/documents/{$document->id}/download")
            ->assertOk();
    }

    public function test_private_document_downloads_are_scoped_to_the_application(): void
    {
        [, $application, $document] = $this->makeGroupApplication('phase3-download');
        [, , $otherDocument] = $this->makeGroupApplication('phase3-other');

        $this->withToken($this->adminToken())
            ->get("/api/v1/dashboard/group-applications/{$application->id}/documents/{$document->id}/download")
            ->assertOk();

        $this->withToken($this->adminToken())
            ->get("/api/v1/dashboard/group-applications/{$application->id}/documents/{$otherDocument->id}/download")
            ->assertNotFound();
    }

    public function test_admin_without_review_permission_is_forbidden(): void
    {
        $this->makeGroupApplication('phase3-forbidden');
        $admin = Admins::create([
            'name' => 'No Review Admin',
            'email' => 'no-review@example.test',
            'phone' => '+966500099999',
            'password' => 'P@ssw0rd123',
            'is_active' => true,
        ]);
        $token = $admin->createToken('admin-token', ['admins'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/dashboard/group-applications')
            ->assertForbidden();
    }

    public function test_admin_can_suspend_and_reactivate_an_approved_group_workspace(): void
    {
        [$tenant, $application, $document] = $this->makeGroupApplication('phase3-suspend');
        $token = $this->adminToken();

        $this->withToken($token)->postJson("/api/v1/dashboard/group-applications/{$application->id}/documents/{$document->id}/accept")->assertOk();
        $this->withToken($token)->postJson("/api/v1/dashboard/group-applications/{$application->id}/approve")->assertOk();

        $this->withToken($token)->patchJson("/api/v1/dashboard/group-applications/{$application->id}/suspend", [
            'reason' => 'Temporary policy review.',
        ])->assertOk();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'suspended']);

        $this->withToken($token)->patchJson("/api/v1/dashboard/group-applications/{$application->id}/reactivate")->assertOk();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'active']);
        $this->assertDatabaseHas('group_application_status_histories', [
            'application_id' => $application->id,
            'action' => 'workspace_suspended',
        ]);
        $this->assertDatabaseHas('group_application_status_histories', [
            'application_id' => $application->id,
            'action' => 'workspace_reactivated',
        ]);
    }

    public function test_admin_dashboard_counts_include_group_applications(): void
    {
        $this->makeGroupApplication('phase3-counts');

        $this->withToken($this->adminToken())
            ->getJson('/api/v1/dashboard/counts')
            ->assertOk()
            ->assertJsonPath('data.theater_groups_count', 1)
            ->assertJsonPath('data.pending_group_applications_count', 1);
    }

    public function test_review_notifications_are_created_for_admins_and_group_owners(): void
    {
        [$tenant, $application, $document] = $this->makeGroupApplication('phase3-notifications');
        $owner = $tenant->members()->where('is_owner', true)->firstOrFail();

        $this->assertDatabaseHas('app_notifications', [
            'notifiable_type' => Admins::class,
            'type' => 'application_submitted',
        ]);

        $this->withToken($this->adminToken())
            ->postJson("/api/v1/dashboard/group-applications/{$application->id}/documents/{$document->id}/accept")
            ->assertOk();

        $this->assertDatabaseHas('app_notifications', [
            'notifiable_type' => TenantMember::class,
            'notifiable_id' => $owner->id,
            'type' => 'document_accepted',
        ]);
    }

    /**
     * @return array{0: Tenant, 1: GroupApplication, 2: GroupApplicationDocument}
     */
    private function makeGroupApplication(string $subdomain): array
    {
        $result = app(TenantRegistrationService::class)->createGroup(
            [
                'name' => ucfirst(str_replace('-', ' ', $subdomain)),
                'subdomain' => $subdomain,
                'contact_person_name' => 'Group Owner',
                'email' => 'owner@'.$subdomain.'.test',
                'phone' => '+9665000'.random_int(10000, 99999),
                'password' => 'P@ssw0rd123',
                'description' => 'A Phase 3 test group.',
            ],
            UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf')
        );

        /** @var GroupApplication $application */
        $application = $result['application']->fresh(['documents', 'tenant.members']);

        return [
            $result['tenant'],
            $application,
            $application->documents->firstOrFail(),
        ];
    }

    private function adminToken(): string
    {
        return $this->postJson('/api/v1/login', [
            'step' => 1,
            'email' => 'admin@base.com',
            'password' => 'P@ssw0rd',
        ])->assertOk()->json('data.token');
    }
}
