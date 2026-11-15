<?php

use Modules\Admins\Enums\ActiveStatus;

if (! defined('BASE_ADMIN')) {
    define('BASE_ADMIN', 1);
}
if (! defined('PASSWORD')) {
    define('PASSWORD', 'P@ssw0rd');
}
if (! defined('FETCH')) {
    define('FETCH', 'fetch');
}
if (! defined('CREATE')) {
    define('CREATE', 'create');
}
if (! defined('READ')) {
    define('READ', 'read');
}
if (! defined('UPDATE')) {
    define('UPDATE', 'update');
}
if (! defined('DELETE')) {
    define('DELETE', 'delete');
}

return [
    'name' => 'Admins',

    /*
     * Base admins config (for seeding)
     */
    'base_role' => [1, 2],
    'base_admins' => [
        [
            'id' => BASE_ADMIN,
            'name' => 'Admin',
            'email' => 'admin@base.com',
            'password' => PASSWORD,
            'is_active' => App\Enums\ActiveStatus::ACTIVE,
            'phone' => '000000000000',
        ],
    ],

    /*
     * Base roles config (from Roles module)
     */
    'base_roles' => [
        'default_role' => [
            'id' => 1,
            'name' => ['ar' => 'دور تقليدى', 'en' => 'Default Role'],
            'description' => ['ar' => 'وصف الدور التقليدى', 'en' => 'Default Role Description'],
            'is_active' => App\Enums\ActiveStatus::ACTIVE,
        ],
        'super_role' => [
            'id' => 2,
            'name' => ['ar' => 'دور المشرف الرئيسي', 'en' => 'Super Admin Role '],
            'description' => ['ar' => 'وصف دور المشرف الرئيسي', 'en' => 'Super Admin Role Description'],
            'is_active' => App\Enums\ActiveStatus::ACTIVE,
        ],
    ],

    /*
     * Permissions config (from Models module)
     */
    'permissions' => [
        ['id' => 1, 'name' => FETCH, 'title' => ['en' => 'Fetch Data', 'ar' => 'إظهار البيانات']],
        ['id' => 2, 'name' => CREATE, 'title' => ['en' => 'Create Data', 'ar' => 'إضافة البيانات']],
        ['id' => 3, 'name' => READ, 'title' => ['en' => 'Read Data',  'ar' => 'إظهار التفاصيل']],
        ['id' => 4, 'name' => UPDATE, 'title' => ['en' => 'Update Data', 'ar' => 'تعديل البيانات']],
        ['id' => 5, 'name' => DELETE, 'title' => ['en' => 'Delete Data', 'ar' => 'حذف البيانات']],
    ],

    /*
     * Modules config (from Models module)
     */
    'modules' => [
        'users' => [
            'title' => ['en' => 'Users', 'ar' => 'المستخدمون'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'theaters' => [
            'title' => ['en' => 'Theaters', 'ar' => 'المسارح'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'theater_groups' => [
            'title' => ['en' => 'Theater Groups', 'ar' => 'الفرق المسرحية'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'group_applications' => [
            'title' => ['en' => 'Group Applications', 'ar' => 'طلبات الفرق'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'halls' => [
            'title' => ['en' => 'Halls', 'ar' => 'القاعات'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'events' => [
            'title' => ['en' => 'Events', 'ar' => 'الفعاليات'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'reservations' => [
            'title' => ['en' => 'Reservations', 'ar' => 'الحجوزات'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'bookings' => [
            'title' => ['en' => 'Bookings', 'ar' => 'حجوزات التذاكر'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'reports' => [
            'title' => ['en' => 'Reports', 'ar' => 'التقارير'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'team_management' => [
            'title' => ['en' => 'Team Management', 'ar' => 'إدارة الفريق'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
        'settings' => [
            'title' => ['en' => 'Settings', 'ar' => 'الإعدادات'],
            'is_active' => ActiveStatus::ACTIVE,
            'permissions' => [1 => FETCH, 2 => CREATE, 3 => READ, 4 => UPDATE, 5 => DELETE],
        ],
    ],
];
