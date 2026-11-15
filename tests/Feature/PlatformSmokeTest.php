<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Database\Seeders\LocationSeeder;
use Tests\TestCase;

class PlatformSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.enabled' => false]);
        $this->seed(LocationSeeder::class);
    }

    public function test_public_location_lookups_are_available_without_a_token(): void
    {
        $this->getJson('/api/v1/locations/country')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['phone_code' => '+966']);
    }

    public function test_central_settings_are_not_public(): void
    {
        $this->getJson('/api/v1/settings/billing')
            ->assertStatus(401);
    }
}
