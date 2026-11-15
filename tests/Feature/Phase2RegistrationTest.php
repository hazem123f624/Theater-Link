<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Settings\Database\Seeders\LocationSeeder;
use Modules\Settings\Models\Settings;
use Modules\Tenancy\Models\GroupApplication;
use Modules\Tenancy\Services\TenantProvisioningService;
use Tests\TestCase;

class Phase2RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        $this->seed(LocationSeeder::class);
    }

    public function test_a_theater_can_register_and_verify_its_owner(): void
    {
        $countryId = Settings::query()->where('type', 'country')->where('key', 'SA')->value('id');
        $cityId = Settings::query()->where('type', 'city')->where('setting_id', $countryId)->value('id');

        $this->postJson('/api/v1/theater/register', [
            'name' => 'Al Noor Theater',
            'subdomain' => 'al-noor-register',
            'owner_name' => 'Theater Owner',
            'email' => 'owner@al-noor-register.test',
            'phone' => '+966500000101',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'address' => 'Riyadh',
            'country_id' => $countryId,
            'city_id' => $cityId,
        ])->assertOk()->assertJsonPath('data.otp', 1234);

        $this->postJson('/api/v1/theater/verify', [
            'phone' => '+966500000101',
            'otp' => '1234',
        ])->assertOk()
            ->assertJsonPath('data.profile.name', 'Al Noor Theater')
            ->assertJsonStructure(['data' => ['member' => ['token', 'tenant']]]);
    }

    public function test_a_group_can_register_with_a_permit_and_then_see_its_pending_application(): void
    {
        $register = $this->post('/api/v1/group/register', [
            'name' => 'Mask Group',
            'subdomain' => 'mask-group-register',
            'contact_person_name' => 'Group Owner',
            'email' => 'owner@mask-group-register.test',
            'phone' => '+966500000102',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'description' => 'A theater group',
            'permit' => UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $register->assertOk()->assertJsonPath('data.otp', 1234);

        $verify = $this->postJson('/api/v1/group/verify', [
            'phone' => '+966500000102',
            'otp' => '1234',
        ]);

        $verify->assertOk()
            ->assertJsonPath('data.application.status', 'pending_review')
            ->assertJsonStructure(['data' => ['member' => ['token'], 'application' => ['reference', 'documents']]]);

        $token = $verify->json('data.member.token');
        $applicationId = $verify->json('data.application.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/group/application')
            ->assertOk()
            ->assertJsonPath('data.id', $applicationId)
            ->assertJsonPath('data.status', 'pending_review');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/group/dashboard')
            ->assertStatus(403);

        $this->assertDatabaseHas('group_applications', ['id' => $applicationId, 'status' => 'pending_review']);
        $this->assertDatabaseCount('group_application_documents', 1);
    }

    public function test_theater_registration_rejects_an_existing_subdomain(): void
    {
        app(TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => 'Existing Theater', 'subdomain' => 'existing-theater'],
            ['name' => 'Owner', 'email' => 'owner@existing-theater.test', 'phone' => '+966500000104', 'password' => 'P@ssw0rd123']
        );

        $this->postJson('/api/v1/theater/register', [
            'name' => 'Duplicate Theater',
            'subdomain' => 'existing-theater',
            'owner_name' => 'Another Owner',
            'email' => 'owner@duplicate-theater.test',
            'phone' => '+966500000105',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('subdomain');
    }

    public function test_tenant_members_can_reset_their_password_with_an_otp(): void
    {
        app(\Modules\Tenancy\Services\TenantProvisioningService::class)->provision(
            ['type' => 'theater', 'name' => 'Password Theater', 'subdomain' => 'password-theater'],
            ['name' => 'Owner', 'email' => 'owner@password-theater.test', 'phone' => '+966500000103', 'password' => 'P@ssw0rd123']
        );

        $request = $this->postJson('/api/v1/tenant/forgot-password', [
            'subdomain' => 'password-theater',
            'email' => 'owner@password-theater.test',
        ])->assertOk();

        $token = $request->json('data.change_password_token');

        $this->postJson('/api/v1/tenant/verify-forgot-password', [
            'subdomain' => 'password-theater',
            'email' => 'owner@password-theater.test',
            'otp' => '1234',
            'change_password_token' => $token,
        ])->assertOk();

        $this->postJson('/api/v1/tenant/reset-password', [
            'subdomain' => 'password-theater',
            'email' => 'owner@password-theater.test',
            'otp' => '1234',
            'change_password_token' => $token,
            'password' => 'NewP@ssw0rd123',
            'password_confirmation' => 'NewP@ssw0rd123',
        ])->assertOk();

        $this->postJson('/api/v1/theater/login', [
            'subdomain' => 'password-theater',
            'email' => 'owner@password-theater.test',
            'password' => 'NewP@ssw0rd123',
        ])->assertOk();
    }
}
