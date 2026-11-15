<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenancy\Database\Seeders\TenancyDatabaseSeeder;
use Modules\Tenancy\Models\Permission;
use Modules\Tenancy\Models\TenantRole;
use Modules\Tenancy\Services\TenantProvisioningService;
use Tests\TestCase;

class TenantRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        $this->seed(TenancyDatabaseSeeder::class);
    }

    public function test_an_owner_can_create_update_and_delete_a_custom_role(): void
    {
        $tenant = $this->provisionTheater('role-theater');
        $token = $this->login('role-theater', 'owner@role-theater.test');

        $permissions = Permission::query()
            ->whereIn('name', ['events.fetch', 'events.create'])
            ->pluck('id')
            ->all();

        $create = $this->withToken($token)->postJson('/api/v1/tenant/roles', [
            'name' => 'Event Coordinator',
            'permission_ids' => $permissions,
        ])->assertCreated();

        $roleId = $create->json('data.id');

        $this->withToken($token)->putJson('/api/v1/tenant/roles/'.$roleId, [
            'name' => 'Senior Event Coordinator',
            'permission_ids' => [$permissions[0]],
        ])->assertOk()
            ->assertJsonPath('data.name', 'Senior Event Coordinator');

        $this->withToken($token)->deleteJson('/api/v1/tenant/roles/'.$roleId)->assertOk();

        $this->assertSoftDeleted('tenant_roles', ['id' => $roleId]);
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $tenant = $this->provisionTheater('system-role-theater');
        $token = $this->login('system-role-theater', 'owner@system-role-theater.test');

        $ownerRole = TenantRole::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('template_key', 'owner')
            ->firstOrFail();

        $this->withToken($token)
            ->deleteJson('/api/v1/tenant/roles/'.$ownerRole->id)
            ->assertStatus(422);
    }

    public function test_a_group_cannot_assign_a_theater_only_permission(): void
    {
        $tenant = app(TenantProvisioningService::class)->provision(
            ['type' => 'group', 'name' => 'Group Roles', 'subdomain' => 'group-roles'],
            ['name' => 'Owner', 'email' => 'owner@group-roles.test', 'password' => 'P@ssw0rd123']
        );
        app(TenantProvisioningService::class)->approve($tenant);

        $token = $this->postJson('/api/v1/group/login', [
            'subdomain' => 'group-roles',
            'email' => 'owner@group-roles.test',
            'password' => 'P@ssw0rd123',
        ])->assertOk()->json('data.token');

        $theaterOnly = Permission::query()->where('name', 'events.fetch')->firstOrFail();

        $this->withToken($token)->postJson('/api/v1/tenant/roles', [
            'name' => 'Invalid Group Role',
            'permission_ids' => [$theaterOnly->id],
        ])->assertStatus(422);
    }

    private function provisionTheater(string $subdomain): \Modules\Tenancy\Models\Tenant
    {
        return app(TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => ucfirst($subdomain), 'subdomain' => $subdomain],
            [
                'name' => 'Owner',
                'email' => 'owner@'.$subdomain.'.test',
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
}
