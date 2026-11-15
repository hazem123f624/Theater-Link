<?php

return [
    'single_factor_auth' => true,

    'test' => [
        'token' => env('AUTH_TEST_TOKEN', 'TheaterLinkLocalTestToken'),
        'otp' => '1234',
    ],

    'types' => [
        'admins' => [
            'model' => \Modules\Admins\Models\Admins::class,
            'guard' => 'admins',
            'dtoClass' => \Modules\Admins\DTOs\AdminsDTO::class,
        ],
        'users' => [
            'model' => \Modules\Users\Models\User::class,
            'guard' => 'users',
            'dtoClass' => \Modules\Users\DTOs\UsersDTO::class,
        ],
        'tenant_members' => [
            'model' => \Modules\Tenancy\Models\TenantMember::class,
            'guard' => 'tenant_members',
            'dtoClass' => \Modules\Tenancy\DTOs\TenantMemberDTO::class,
        ],
    ],
];
