<?php

namespace Modules\Admins\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Modules\Admins\Models\Admins;

class AdminsFactory extends Factory
{
    protected $model = Admins::class;

    public function definition(): array
    {
        $data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'password' => Hash::make('password'),
            'is_active' => true,
        ];

        if (config('tenant.enabled', false)) {
            $data['tenant_id'] = 'default-tenant';
        }

        return $data;
    }
}
