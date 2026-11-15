<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admins\Database\Seeders\AdminsDatabaseSeeder;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_sign_in_to_the_central_dashboard(): void
    {
        $this->seed(AdminsDatabaseSeeder::class);

        $this->postJson('/api/v1/login', [
            'step' => 1,
            'email' => 'admin@base.com',
            'password' => 'P@ssw0rd',
        ])->assertOk()
            ->assertJsonPath('data.email', 'admin@base.com')
            ->assertJsonStructure(['data' => ['token', 'roles', 'permissions']]);
    }
}
