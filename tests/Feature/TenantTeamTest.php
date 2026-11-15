<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder;
use Modules\Tenancy\Models\Tenant;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Services\TenantProvisioningService;
use Tests\TestCase;

class TenantTeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        $this->seed(TenancyDatabaseSeeder::class);
    }

    public function test_owner_can_create_and_list_a_team_member(): void
    {
        $tenant = $this->provisionTheater('team-a');
        $token = $this->login('team-a', 'owner@team-a.test');

        $viewerRole = TenantRole::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('template_key', 'viewer')
            ->firstOrFail();

        $create = $this->withToken($token)->postJson('/api/v1/tenant/team', [
            'name' => 'Box Office',
            'email' => 'box@team-a.test',
            'phone' => '966500000010',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'roles' => [$viewerRole->id],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.email', 'box@team-a.test')
            ->assertJsonPath('data.is_owner', false);

        $this->withToken($token)
            ->getJson('/api/v1/tenant/team')
            ->assertOk()
            ->assertJsonPath('pagination.total', 2);
    }

    public function test_viewer_cannot_list_team_members(): void
    {
        $tenant = $this->provisionTheater('team-b');
        $ownerToken = $this->login('team-b', 'owner@team-b.test');

        $viewerRole = TenantRole::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('template_key', 'viewer')
            ->firstOrFail();

        $this->withToken($ownerToken)->postJson('/api/v1/tenant/team', [
            'name' => 'Viewer',
            'email' => 'viewer@team-b.test',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'roles' => [$viewerRole->id],
        ])->assertCreated();

        $viewerToken = $this->login('team-b', 'viewer@team-b.test');

        $this->app['auth']->forgetGuards();

        $this->withToken($viewerToken)
            ->getJson('/api/v1/tenant/team')
            ->assertStatus(403);
    }

    public function test_a_tenant_cannot_update_another_tenants_member(): void
    {
        $this->provisionTheater('team-c');
        $otherTenant = $this->provisionTheater('team-d');

        $tokenC = $this->login('team-c', 'owner@team-c.test');
        $tokenD = $this->login('team-d', 'owner@team-d.test');

        $member = \Modules\Tenancy\Models\TenantMember::query()
            ->where('tenant_id', $otherTenant->id)
            ->where('email', 'owner@team-d.test')
            ->firstOrFail();

        $this->app['auth']->forgetGuards();

        $this->withToken($tokenC)
            ->putJson('/api/v1/tenant/team/'.$member->id, ['name' => 'Hijacked'])
            ->assertStatus(404);

        $this->app['auth']->forgetGuards();

        $this->withToken($tokenD)
            ->putJson('/api/v1/tenant/team/'.$member->id, ['name' => 'Renamed Owner'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Owner');
    }

    public function test_last_owner_cannot_be_deactivated(): void
    {
        $tenant = $this->provisionTheater('team-e');
        $token = $this->login('team-e', 'owner@team-e.test');

        $owner = \Modules\Tenancy\Models\TenantMember::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_owner', true)
            ->firstOrFail();

        $this->withToken($token)
            ->patchJson('/api/v1/tenant/team/'.$owner->id.'/toggle-active')
            ->assertStatus(422);
    }

    private function provisionTheater(string $subdomain): Tenant
    {
        return app(TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => ucfirst($subdomain), 'subdomain' => $subdomain],
            [
                'name' => 'Owner',
                'email' => "owner@{$subdomain}.test",
                'phone' => '96650000'.substr(md5($subdomain), 0, 4),
                'password' => 'P@ssw0rd123',
            ]
        );
    }

    private function login(string $subdomain, string $email): string
    {
        return $this->postJson('/api/v1/theater/login', [
            'subdomain' => $subdomain,
            'email' => $email,
            'password' => 'P@ssw0rd123',
        ])->assertOk()->json('data.token');
    }
    public function test_owner_can_list_tenant_roles(): void
    {
        $this->provisionTheater('team-f');
        $token = $this->login('team-f', 'owner@team-f.test');

        $this->withToken($token)
            ->getJson('/api/v1/tenant/roles')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'permissions']]]);
    }

    public function test_a_removed_member_email_can_be_used_again(): void
    {
        $tenant = $this->provisionTheater('team-g');
        $token = $this->login('team-g', 'owner@team-g.test');

        $viewerRole = TenantRole::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('template_key', 'viewer')
            ->firstOrFail();

        $created = $this->withToken($token)->postJson('/api/v1/tenant/team', [
            'name' => 'Temporary Member',
            'email' => 'temporary@team-g.test',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'roles' => [$viewerRole->id],
        ])->assertCreated();

        $memberId = $created->json('data.id');

        $this->withToken($token)
            ->deleteJson('/api/v1/tenant/team/'.$memberId)
            ->assertOk();

        $this->withToken($token)->postJson('/api/v1/tenant/team', [
            'name' => 'Replacement Member',
            'email' => 'temporary@team-g.test',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'roles' => [$viewerRole->id],
        ])->assertCreated();
    }

    public function test_a_removed_member_can_be_restored_and_permanently_deleted(): void
    {
        $tenant = $this->provisionTheater('team-h');
        $token = $this->login('team-h', 'owner@team-h.test');

        $viewerRole = TenantRole::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('template_key', 'viewer')
            ->firstOrFail();

        $memberId = $this->withToken($token)->postJson('/api/v1/tenant/team', [
            'name' => 'Restorable',
            'email' => 'restorable@team-h.test',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'roles' => [$viewerRole->id],
        ])->assertCreated()->json('data.id');

        $this->withToken($token)->deleteJson('/api/v1/tenant/team/'.$memberId)->assertOk();

        $this->withToken($token)->getJson('/api/v1/tenant/team/trash')
            ->assertOk()
            ->assertJsonFragment(['id' => $memberId]);

        $this->withToken($token)->patchJson('/api/v1/tenant/team/'.$memberId.'/restore')->assertOk();

        $this->withToken($token)->deleteJson('/api/v1/tenant/team/'.$memberId)->assertOk();
        $this->withToken($token)->deleteJson('/api/v1/tenant/team/'.$memberId.'/force-delete')->assertOk();

        $this->assertDatabaseMissing('tenant_members', ['id' => $memberId]);
    }

}
