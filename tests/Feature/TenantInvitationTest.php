<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Services\TenantProvisioningService;
use Tests\TestCase;

class TenantInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        $this->seed(\Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder::class);
    }

    public function test_an_owner_can_invite_a_member_and_the_member_can_accept(): void
    {
        $tenant = app(TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => 'Invite Theater', 'subdomain' => 'invite-theater'],
            ['name' => 'Owner', 'email' => 'owner@invite-theater.test', 'password' => 'P@ssw0rd123']
        );

        $ownerToken = $this->postJson('/api/v1/theater/login', [
            'subdomain' => 'invite-theater',
            'email' => 'owner@invite-theater.test',
            'password' => 'P@ssw0rd123',
        ])->json('data.token');

        $viewerRole = TenantRole::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('template_key', 'viewer')
            ->firstOrFail();

        $invite = $this->withToken($ownerToken)->postJson('/api/v1/tenant/team/invitations', [
            'email' => 'invitee@invite-theater.test',
            'name' => 'Invitee',
            'role_id' => $viewerRole->id,
        ])->assertCreated();

        $token = $invite->json('data.token');

        $this->postJson('/api/v1/tenant/team/invitations/accept', [
            'token' => $token,
            'name' => 'Invitee',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
        ])->assertOk()
            ->assertJsonPath('data.email', 'invitee@invite-theater.test')
            ->assertJsonStructure(['data' => ['token', 'tenant']]);
    }
}
