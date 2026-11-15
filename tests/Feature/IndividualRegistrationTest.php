<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Database\Seeders\LocationSeeder;
use Tests\TestCase;

class IndividualRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        $this->seed(LocationSeeder::class);
    }

    public function test_an_individual_can_register_and_verify_with_an_otp(): void
    {
        $register = $this->postJson('/api/v1/user/register', [
            'name' => 'Audience Member',
            'country_code' => '+966',
            'phone' => '500000001',
            'email' => 'member@example.test',
            'password' => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
        ]);

        $register->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.otp', 1234);

        $this->postJson('/api/v1/user/verify', [
            'country_code' => '+966',
            'phone' => '500000001',
            'otp' => '1234',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', '+966500000001')
            ->assertJsonStructure(['data' => ['token']]);

        $login = $this->postJson('/api/v1/user/login', [
            'country_code' => '+966',
            'phone' => '500000001',
            'password' => 'P@ssw0rd123',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token']]);

        $this->withHeader('Authorization', 'Bearer '.$login->json('data.token'))
            ->getJson('/api/v1/tenant/me')
            ->assertStatus(403);

        $this->withHeader('Authorization', 'Bearer '.$login->json('data.token'))
            ->getJson('/api/v1/user/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
