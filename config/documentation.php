<?php

return [
    'name' => 'Documentations',

    'default_consumer' => 'Admin Dashboard API',

    /*
    | API surfaces. The longest matching route prefix wins.
    */
    'consumers' => [
        'Theater Workspace API' => ['theater'],
        'Group Workspace API' => ['group'],
        'Tenant API' => ['tenant'],
        'Admin Dashboard API' => ['dashboard', 'admin', 'settings', 'locations'],
        'User Dashboard API' => ['user'],
        'Public API' => ['public', 'web'],
        'Internal API' => ['internal', 'security'],
        'Notifications API' => ['notifications'],
    ],

    'group_threshold' => (int) env('API_DOCS_GROUP_THRESHOLD', 5),

    'module_order' => [
        'Theater Workspace API' => ['Authentication', 'Dashboard', 'Theater Profile', 'Halls', 'Hall Schedules', 'Hall Availability', 'Events', 'Shows', 'Ticket Tiers'],
        'Group Workspace API' => ['Authentication', 'Dashboard', 'Theater Group'],
        'Tenant API' => ['Authentication', 'Tenancy', 'Team', 'Roles'],
        'Admin Dashboard API' => ['Authentication', 'Administration', 'Group Applications', 'Users', 'Settings', 'Locations', 'Activity Logs'],
        'User Dashboard API' => ['Authentication', 'Users', 'Dashboard'],
        'Internal API' => ['Security', 'Tenancy'],
        'Notifications API' => ['Notifications'],
        'Public API' => ['Authentication', 'Locations', 'Public Catalog'],
    ],

    'group_order' => [
        'Login & Session' => 10,
        'Profile' => 20,
        'Team' => 30,
        'Roles' => 40,
        'Administration' => 50,
        'Registration & Verification' => 10,
        'Notifications' => 15,
        'Password' => 20,
        'Common' => 100,
    ],

    'endpoint_names' => [
        'POST /api/v1/theater/login' => 'Theater Workspace Login',
        'POST /api/v1/group/login' => 'Group Workspace Login',
        'GET /api/v1/tenant/me' => 'Get Tenant Profile',
        'POST /api/v1/tenant/logout' => 'Tenant Logout',
        'POST /api/v1/tenant/refresh-token' => 'Refresh Tenant Token',
        'GET /api/v1/tenant/team' => 'List Team Members',
        'POST /api/v1/tenant/team' => 'Create Team Member',
        'PUT /api/v1/tenant/team/{member}' => 'Update Team Member',
        'PATCH /api/v1/tenant/team/{member}/toggle-active' => 'Toggle Team Member Status',
        'DELETE /api/v1/tenant/team/{member}' => 'Remove Team Member',
        'GET /api/v1/tenant/roles' => 'List Tenant Roles',
    ],

    /*
    | Explicit consumer/module assignments where the URL prefix is ambiguous.
    */
    'overrides' => [
        ['route' => 'theater/login', 'exact' => true, 'consumer' => 'Theater Workspace API', 'module' => 'Authentication'],
        ['route' => 'group/login', 'exact' => true, 'consumer' => 'Group Workspace API', 'module' => 'Authentication'],
        ['route' => 'tenant', 'consumer' => 'Tenant API', 'module' => 'Tenancy'],
        ['route' => 'user', 'consumer' => 'User Dashboard API', 'module' => 'Users'],
        ['route' => 'dashboard', 'consumer' => 'Admin Dashboard API', 'module' => 'Administration'],
        ['route' => 'settings', 'consumer' => 'Admin Dashboard API', 'module' => 'Settings'],
        ['route' => 'locations', 'consumer' => 'Public API', 'module' => 'Locations'],
        ['route' => 'security', 'consumer' => 'Internal API', 'module' => 'Security'],
        ['route' => 'login', 'exact' => true, 'consumer' => 'Admin Dashboard API', 'module' => 'Authentication'],
        ['route' => 'logout', 'exact' => true, 'consumer' => 'Admin Dashboard API', 'module' => 'Authentication'],
        ['route' => 'forget-password', 'exact' => true, 'consumer' => 'Admin Dashboard API', 'module' => 'Authentication'],
        ['route' => 'verify-forget-password-otp', 'exact' => true, 'consumer' => 'Admin Dashboard API', 'module' => 'Authentication'],
        ['route' => 'reset-password', 'exact' => true, 'consumer' => 'Admin Dashboard API', 'module' => 'Authentication'],
        ['route' => 'resend-otp', 'exact' => true, 'consumer' => 'Admin Dashboard API', 'module' => 'Authentication'],
        ['route' => 'resend-forgot-password-otp', 'exact' => true, 'consumer' => 'Admin Dashboard API', 'module' => 'Authentication'],
    ],

    'modules' => [
        'login' => 'Authentication',
        'logout' => 'Authentication',
        'refresh-token' => 'Authentication',
        'me' => 'Authentication',
        'team' => 'Team',
        'tenancy' => 'Tenancy',
        'admins' => 'Administration',
        'roles' => 'Administration',
        'models' => 'Administration',
        'counts' => 'Administration',
        'profile' => 'Authentication',
        'change-password' => 'Authentication',
        'activity-logs' => 'Activity Logs',
        'users' => 'Users',
        'settings' => 'Settings',
        'locations' => 'Locations',
        'security' => 'Security',
        'dashboard' => 'Dashboard',
        'register' => 'Authentication',
        'verify' => 'Authentication',
        'resend-otp' => 'Authentication',
        'application' => 'Group Application',
    ],
];
