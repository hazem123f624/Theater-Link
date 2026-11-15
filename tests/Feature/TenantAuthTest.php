<?php

namespace Tests\Feature;

use App\Services\Security\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder;
use Modules\Tenancy\Services\TenantProvisioningService;
use Tests\TestCase;

class TenantAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        $this->seed(TenancyDatabaseSeeder::class);
    }

    public function test_theater_login_returns_a_workspace_bound_token(): void
    {
        app(TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => 'Al Noor Theater', 'subdomain' => 'al-noor'],
            ['name' => 'Theater Owner', 'email' => 'owner@al-noor.test', 'phone' => '966500000001', 'password' => 'P@ssw0rd123']
        );

        $response = $this->postJson('/api/v1/theater/login', [
            'subdomain' => 'al-noor',
            'email' => 'owner@al-noor.test',
            'password' => 'P@ssw0rd123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tenant.type', 'theater')
            ->assertJsonPath('data.is_owner', true)
            ->assertJsonStructure(['data' => ['token', 'tenant', 'permissions']]);

        $this->assertContains('halls.fetch', $response->json('data.permissions'));

        $this->withHeader('Authorization', 'Bearer '.$response->json('data.token'))
            ->getJson('/api/v1/user/profile')
            ->assertStatus(403);
    }

    public function test_a_theater_cannot_log_in_through_the_group_endpoint(): void
    {
        app(TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => 'Al Noor Theater', 'subdomain' => 'al-noor'],
            ['name' => 'Theater Owner', 'email' => 'owner@al-noor.test', 'password' => 'P@ssw0rd123']
        );

        $this->postJson('/api/v1/group/login', [
            'subdomain' => 'al-noor',
            'email' => 'owner@al-noor.test',
            'password' => 'P@ssw0rd123',
        ])->assertStatus(422);
    }

    public function test_tenant_context_rejects_a_foreign_subdomain_header(): void
    {
        $tenant = app(TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => 'Al Noor Theater', 'subdomain' => 'al-noor'],
            ['name' => 'Theater Owner', 'email' => 'owner@al-noor.test', 'password' => 'P@ssw0rd123']
        );

        $login = $this->postJson('/api/v1/theater/login', [
            'subdomain' => 'al-noor',
            'email' => 'owner@al-noor.test',
            'password' => 'P@ssw0rd123',
        ]);

        $token = $login->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Subdomain', 'another-theater')
            ->getJson('/api/v1/tenant/me')
            ->assertStatus(404);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Subdomain', 'al-noor')
            ->withHeader('X-Tenant', $tenant->ulid)
            ->getJson('/api/v1/tenant/me')
            ->assertOk()
            ->assertJsonPath('data.tenant.id', $tenant->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Subdomain', 'al-noor')
            ->withHeader('X-Tenant', ApiKeyService::encrypt($tenant->ulid, $tenant->uuid))
            ->getJson('/api/v1/tenant/me')
            ->assertOk();
    }
    public function test_pending_group_can_sign_in_but_cannot_use_workspace_routes(): void
    {
        app(TenantProvisioningService::class)->provision(
            ['type' => 'group', 'name' => 'Mask Group', 'subdomain' => 'mask-group'],
            ['name' => 'Group Owner', 'email' => 'owner@mask-group.test', 'password' => 'P@ssw0rd123']
        );

        $login = $this->postJson('/api/v1/group/login', [
            'subdomain' => 'mask-group',
            'email' => 'owner@mask-group.test',
            'password' => 'P@ssw0rd123',
        ]);

        $login->assertOk()
            ->assertJsonPath('data.tenant.status', 'pending_review');

        $this->assertNotContains('halls.fetch', $login->json('data.permissions'));

        $this->withHeader('Authorization', 'Bearer '.$login->json('data.token'))
            ->getJson('/api/v1/tenant/team')
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer '.$login->json('data.token'))
            ->getJson('/api/v1/group/dashboard')
            ->assertStatus(403);
    }

    public function test_suspended_theater_login_is_refused_with_a_workspace_error(): void
    {
        $tenant = app(TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => 'Closed Theater', 'subdomain' => 'closed-theater'],
            ['name' => 'Closed Owner', 'email' => 'owner@closed-theater.test', 'password' => 'P@ssw0rd123']
        );

        $tenant->update(['status' => 'suspended']);

        $this->postJson('/api/v1/theater/login', [
            'subdomain' => 'closed-theater',
            'email' => 'owner@closed-theater.test',
            'password' => 'P@ssw0rd123',
        ])->assertStatus(403)
            ->assertJsonPath('message', 'This workspace is not active yet.');
    }

    public function test_an_approved_group_can_open_the_group_dashboard(): void
    {
        $tenant = app(TenantProvisioningService::class)->provision(
            ['type' => 'group', 'name' => 'Approved Group', 'subdomain' => 'approved-group'],
            ['name' => 'Group Owner', 'email' => 'owner@approved-group.test', 'password' => 'P@ssw0rd123']
        );

        app(TenantProvisioningService::class)->approve($tenant);

        $login = $this->postJson('/api/v1/group/login', [
            'subdomain' => 'approved-group',
            'email' => 'owner@approved-group.test',
            'password' => 'P@ssw0rd123',
        ])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$login->json('data.token'))
            ->getJson('/api/v1/group/dashboard')
            ->assertOk()
            ->assertJsonPath('data.tenant.type', 'group');
    }

}
