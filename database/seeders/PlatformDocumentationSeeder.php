<?php

namespace Database\Seeders;

use App\Models\Documentations;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformDocumentationSeeder extends Seeder
{
    private int $sorting = 100;

    public function run(): void
    {
        $endpoints = [
            // Central admin authentication
            ['POST', '/api/v1/login', 'Admin Login', 'Authenticate a central administrator.', 'Login & Session', ['body' => ['step' => 1, 'email' => 'admin@base.com', 'password' => 'P@ssw0rd']]],
            ['POST', '/api/v1/logout', 'Admin Logout', 'Revoke the current administrator token.', 'Login & Session', ['auth' => true]],
            ['POST', '/api/v1/forget-password', 'Admin Forgot Password', 'Request a password-reset OTP.', 'Password Reset', ['body' => ['email' => 'admin@base.com']]],
            ['POST', '/api/v1/verify-forget-password-otp', 'Verify Admin Reset OTP', 'Verify the password-reset OTP and temporary token.', 'Password Reset', ['body' => ['email' => 'admin@base.com', 'otp' => '1234', 'change_password_token' => 'token']]],
            ['POST', '/api/v1/reset-password', 'Reset Admin Password', 'Set a new administrator password.', 'Password Reset', ['body' => ['email' => 'admin@base.com', 'otp' => '1234', 'change_password_token' => 'token', 'password' => 'P@ssw0rd123', 'password_confirmation' => 'P@ssw0rd123']]],
            ['POST', '/api/v1/resend-otp', 'Resend OTP', 'Resend an authentication or password-reset OTP.', 'Login & Session', ['body' => ['email' => 'admin@base.com']]],
            ['POST', '/api/v1/resend-forgot-password-otp', 'Resend Forgotten Password OTP', 'Resend the password-reset OTP.', 'Password Reset', ['body' => ['email' => 'admin@base.com']]],

            // Central admin dashboard
            ['GET', '/api/v1/dashboard/profile', 'Get Admin Profile', 'Return the authenticated administrator profile.', 'Profile', ['auth' => true]],
            ['PUT', '/api/v1/dashboard/profile', 'Update Admin Profile', 'Update the authenticated administrator profile.', 'Profile', ['auth' => true, 'body' => ['name' => 'Admin']]],
            ['POST', '/api/v1/dashboard/change-password', 'Change Admin Password', 'Change the current administrator password.', 'Profile', ['auth' => true, 'body' => ['old_password' => 'old', 'password' => 'new', 'password_confirmation' => 'new']]],
            ['GET', '/api/v1/dashboard/counts', 'Dashboard Counts', 'Return central dashboard counters.', 'Overview', ['auth' => true]],
            ['GET', '/api/v1/dashboard/admins', 'List Administrators', 'List central administrator accounts.', 'Administration', ['auth' => true, 'query' => ['search' => 'admin', 'per_page' => 15]]],
            ['POST', '/api/v1/dashboard/admins', 'Create Administrator', 'Create a central administrator account.', 'Administration', ['auth' => true, 'body' => ['name' => 'Admin', 'email' => 'admin@example.test', 'phone' => '+966500000000', 'password' => 'P@ssw0rd123']]],
            ['GET', '/api/v1/dashboard/admins/{admin}', 'Get Administrator', 'View a central administrator account.', 'Administration', ['auth' => true]],
            ['PUT', '/api/v1/dashboard/admins/{admin}', 'Update Administrator', 'Update a central administrator account.', 'Administration', ['auth' => true]],
            ['DELETE', '/api/v1/dashboard/admins/{admin}', 'Delete Administrator', 'Soft-delete a central administrator account.', 'Administration', ['auth' => true]],
            ['GET', '/api/v1/dashboard/roles', 'List Roles', 'List central administrator roles.', 'Roles', ['auth' => true]],
            ['POST', '/api/v1/dashboard/roles', 'Create Role', 'Create a central administrator role.', 'Roles', ['auth' => true, 'body' => ['name' => ['en' => 'Manager', 'ar' => 'مدير'], 'permissions' => []]]],
            ['GET', '/api/v1/dashboard/roles/{role}', 'Get Role', 'View a central administrator role.', 'Roles', ['auth' => true]],
            ['PUT', '/api/v1/dashboard/roles/{role}', 'Update Role', 'Update a central administrator role.', 'Roles', ['auth' => true]],
            ['DELETE', '/api/v1/dashboard/roles/{role}', 'Delete Role', 'Soft-delete a central administrator role.', 'Roles', ['auth' => true]],
            ['GET', '/api/v1/dashboard/models', 'List Permission Models', 'List the permission modules available to central roles.', 'Models', ['auth' => true]],
            ['GET', '/api/v1/dashboard/activity-logs', 'List Activity Logs', 'List central administrator activity logs.', 'Activity Logs', ['auth' => true, 'query' => ['per_page' => 15]]],
            ['GET', '/api/v1/dashboard/users', 'List Individual Users', 'List individual accounts.', 'User Management', ['auth' => true, 'query' => ['search' => 'user', 'per_page' => 15]]],
            ['GET', '/api/v1/dashboard/users/{user}', 'Get Individual User', 'View an individual account.', 'User Management', ['auth' => true]],
            ['PATCH', '/api/v1/dashboard/users/{user}/toggle-active', 'Toggle Individual User Status', 'Activate or suspend an individual account.', 'User Management', ['auth' => true]],
            ['DELETE', '/api/v1/dashboard/users/{user}', 'Delete Individual User', 'Soft-delete an individual account.', 'User Management', ['auth' => true]],

            ['GET', '/api/v1/dashboard/admins/trash', 'List Deleted Administrators', 'List soft-deleted administrator accounts.', 'Administration', ['auth' => true]],
            ['PATCH', '/api/v1/dashboard/admins/{admin}/restore', 'Restore Administrator', 'Restore a soft-deleted administrator.', 'Administration', ['auth' => true]],
            ['DELETE', '/api/v1/dashboard/admins/{admin}/force-delete', 'Force Delete Administrator', 'Permanently remove a soft-deleted administrator.', 'Administration', ['auth' => true]],
            ['PATCH', '/api/v1/dashboard/admins/{admin}/toggle-active', 'Toggle Administrator Status', 'Activate or deactivate an administrator.', 'Administration', ['auth' => true]],
            ['DELETE', '/api/v1/dashboard/admins/{admin}/roles/{role}', 'Remove Administrator Role', 'Remove a role from an administrator.', 'Role Assignments', ['auth' => true]],
            ['GET', '/api/v1/dashboard/roles/trash', 'List Deleted Roles', 'List soft-deleted central roles.', 'Roles', ['auth' => true]],
            ['PATCH', '/api/v1/dashboard/roles/{role}/restore', 'Restore Role', 'Restore a soft-deleted central role.', 'Roles', ['auth' => true]],
            ['DELETE', '/api/v1/dashboard/roles/{role}/force-delete', 'Force Delete Role', 'Permanently remove a soft-deleted central role.', 'Roles', ['auth' => true]],
            ['PATCH', '/api/v1/dashboard/roles/{role}/toggle-active', 'Toggle Role Status', 'Activate or deactivate a central role.', 'Roles', ['auth' => true]],
            ['GET', '/api/v1/dashboard/roles/{role}/admins', 'List Role Administrators', 'List administrators assigned to a role.', 'Role Assignments', ['auth' => true]],
            ['POST', '/api/v1/dashboard/roles/{role}/admins', 'Assign Administrators To Role', 'Assign one or more administrators to a role.', 'Role Assignments', ['auth' => true]],
            ['GET', '/api/v1/dashboard/roles/{role}/available-admins', 'List Available Administrators', 'List administrators not yet assigned to the role.', 'Role Assignments', ['auth' => true]],
            ['GET', '/api/v1/dashboard/roles/{role}/permissions', 'Get Role Permissions', 'Return the permissions granted to a role.', 'Roles', ['auth' => true]],
            ['GET', '/api/v1/dashboard/users/trash', 'List Deleted Individual Users', 'List soft-deleted individual accounts.', 'User Management', ['auth' => true]],
            ['PATCH', '/api/v1/dashboard/users/{user}/restore', 'Restore Individual User', 'Restore a soft-deleted individual account.', 'User Management', ['auth' => true]],
            ['DELETE', '/api/v1/dashboard/users/{user}/force-delete', 'Force Delete Individual User', 'Permanently remove a soft-deleted individual account.', 'User Management', ['auth' => true]],
            ['GET', '/api/v1/dashboard/activity-logs/{subject_id}', 'Get Activity Log Subject', 'Return the activity history for a subject.', 'Activity Logs', ['auth' => true]],
            ['GET', '/api/v1/dashboard/activity-logs/{subject_id}/export', 'Export Activity Log Subject', 'Export a subject activity history.', 'Activity Logs', ['auth' => true]],
            ['GET', '/api/v1/dashboard/activity-logs/export/{fileName}/download', 'Download Activity Log Export', 'Download a generated activity-log export.', 'Activity Logs', ['auth' => true]],

            // Individual user dashboard
            ['GET', '/api/v1/theater/dashboard', 'Theater Dashboard', 'Return the theater dashboard summary and workspace counts.', 'Overview', ['auth' => true]],
            ['GET', '/api/v1/group/dashboard', 'Group Dashboard', 'Return the group dashboard summary and reservation counts.', 'Overview', ['auth' => true]],
            ['GET', '/api/v1/user/dashboard', 'User Dashboard', 'Return the individual user dashboard summary.', 'Overview', ['auth' => true]],
            ['POST', '/api/v1/user/register', 'Individual Registration', 'Start individual registration and send an OTP.', 'Authentication', ['body' => ['name' => 'Audience Member', 'country_code' => '966', 'phone' => '500000000', 'email' => 'member@example.test', 'password' => 'P@ssw0rd123', 'password_confirmation' => 'P@ssw0rd123']]],
            ['POST', '/api/v1/user/verify', 'Verify Individual Registration', 'Verify the registration OTP and create the individual account.', 'Authentication', ['body' => ['country_code' => '966', 'phone' => '500000000', 'otp' => '1234']]],
            ['POST', '/api/v1/user/login', 'Individual Login', 'Authenticate an individual with phone and password.', 'Authentication', ['body' => ['country_code' => '966', 'phone' => '500000000', 'password' => 'P@ssw0rd123']]],
            ['POST', '/api/v1/user/logout', 'Individual Logout', 'Revoke the current individual token.', 'Authentication', ['auth' => true]],
            ['POST', '/api/v1/user/refresh-token', 'Refresh Individual Token', 'Replace the current individual token.', 'Authentication', ['auth' => true]],
            ['POST', '/api/v1/user/resend-otp', 'Resend Individual OTP', 'Resend a registration or login OTP.', 'Authentication', ['body' => ['country_code' => '966', 'phone' => '500000000']]],
            ['POST', '/api/v1/user/forget-password', 'Individual Forgot Password', 'Request an individual password-reset OTP.', 'Password', ['body' => ['country_code' => '966', 'phone' => '500000000']]],
            ['POST', '/api/v1/user/verify-forget-password', 'Verify Individual Reset OTP', 'Verify the individual password-reset OTP.', 'Password', ['body' => ['country_code' => '966', 'phone' => '500000000', 'otp' => '1234', 'change_password_token' => 'token']]],
            ['POST', '/api/v1/user/reset-password', 'Reset Individual Password', 'Set a new individual password.', 'Password', ['body' => ['country_code' => '966', 'phone' => '500000000', 'otp' => '1234', 'change_password_token' => 'token', 'password' => 'P@ssw0rd123', 'password_confirmation' => 'P@ssw0rd123']]],
            ['GET', '/api/v1/user/profile', 'Get Individual Profile', 'Return the authenticated individual profile.', 'Profile', ['auth' => true]],
            ['POST', '/api/v1/user/profile', 'Update Individual Profile', 'Update the authenticated individual profile.', 'Profile', ['auth' => true, 'body' => ['name' => 'Audience Member']]],
            ['DELETE', '/api/v1/user/profile', 'Delete Individual Account', 'Soft-delete the authenticated individual account.', 'Profile', ['auth' => true]],
            ['POST', '/api/v1/user/change-password', 'Change Individual Password', 'Change the authenticated individual password.', 'Password', ['auth' => true, 'body' => ['old_password' => 'old', 'password' => 'new', 'password_confirmation' => 'new']]],

            // Settings and locations
            ['GET', '/api/v1/settings/{type}', 'List Settings', 'List settings of a given type.', 'Settings', ['auth' => true]],
            ['POST', '/api/v1/settings/{type}', 'Create Setting', 'Create a setting of a given type.', 'Settings', ['auth' => true]],
            ['GET', '/api/v1/settings/{type}/{id}', 'Get Setting', 'View a setting.', 'Settings', ['auth' => true]],
            ['PUT', '/api/v1/settings/{type}/{id}', 'Update Setting', 'Update a setting.', 'Settings', ['auth' => true]],
            ['PATCH', '/api/v1/settings/{type}/{id}/toggle-active', 'Toggle Setting Status', 'Activate or deactivate a setting.', 'Settings', ['auth' => true]],
            ['DELETE', '/api/v1/settings/{type}/{data}', 'Delete Setting', 'Soft-delete a setting.', 'Settings', ['auth' => true]],
            ['GET', '/api/v1/locations/country', 'List Countries', 'List active countries.', 'Locations', ['auth' => false]],
            ['GET', '/api/v1/locations/country/search', 'Search Countries', 'Search active countries by name.', 'Locations', ['auth' => false, 'query' => ['search' => 'Saudi']]],
            ['GET', '/api/v1/locations/city', 'List Cities', 'List active cities.', 'Locations', ['auth' => false]],
            ['GET', '/api/v1/locations/city/search', 'Search Cities', 'Search active cities by name.', 'Locations', ['auth' => false, 'query' => ['search' => 'Riyadh']]],
            ['GET', '/api/v1/locations/country/{country}/cities', 'Cities By Country', 'List cities belonging to a country.', 'Locations', ['auth' => false]],
            ['GET', '/api/v1/locations/nationality', 'List Nationalities', 'List active nationalities.', 'Locations', ['auth' => false]],


            // Phase 2 onboarding and tenant management
            ['POST', '/api/v1/theater/register', 'Theater Registration', 'Start theater registration and send an owner OTP.', 'Registration & Verification', ['body' => ['name' => 'Al Noor Theater', 'subdomain' => 'al-noor', 'owner_name' => 'Theater Owner', 'email' => 'owner@al-noor.test', 'phone' => '+966500000001', 'password' => 'P@ssw0rd123', 'password_confirmation' => 'P@ssw0rd123']]],
            ['POST', '/api/v1/theater/verify', 'Verify Theater Registration', 'Verify the theater owner OTP and activate the workspace.', 'Registration & Verification', ['body' => ['phone' => '+966500000001', 'otp' => '1234']]],
            ['POST', '/api/v1/theater/resend-otp', 'Resend Theater OTP', 'Resend the theater registration OTP.', 'Registration & Verification', ['body' => ['phone' => '+966500000001']]],
            ['POST', '/api/v1/group/register', 'Group Registration', 'Start group registration with a permit upload and owner OTP.', 'Registration & Verification', ['body' => ['name' => 'Mask Group', 'subdomain' => 'mask-group', 'contact_person_name' => 'Group Owner', 'email' => 'owner@mask-group.test', 'phone' => '+966500000002', 'password' => 'P@ssw0rd123', 'password_confirmation' => 'P@ssw0rd123', 'permit' => 'permit.pdf']]],
            ['POST', '/api/v1/group/verify', 'Verify Group Registration', 'Verify the group owner OTP and create the pending application.', 'Registration & Verification', ['body' => ['phone' => '+966500000002', 'otp' => '1234']]],
            ['POST', '/api/v1/group/resend-otp', 'Resend Group OTP', 'Resend the group registration OTP.', 'Registration & Verification', ['body' => ['phone' => '+966500000002']]],
            ['GET', '/api/v1/group/application', 'Get Group Application', 'Return the current group application status and document feedback.', 'Application', ['auth' => true]],
            ['GET', '/api/v1/group/application/documents', 'Get Group Application Documents', 'List the documents attached to the current group application.', 'Application', ['auth' => true]],
            ['GET', '/api/v1/group/application/workspace', 'Get Group Workspace Status', 'Return the group workspace status and address.', 'Application', ['auth' => true]],
            ['GET', '/api/v1/group/application/support', 'Get Group Support Contact', 'Return support contact information for the group.', 'Application', []] ,
            ['POST', '/api/v1/tenant/forgot-password', 'Tenant Forgot Password', 'Request a tenant-member password reset OTP.', 'Password Reset', ['body' => ['subdomain' => 'al-noor', 'email' => 'owner@al-noor.test']]],
            ['POST', '/api/v1/tenant/verify-forgot-password', 'Verify Tenant Reset OTP', 'Verify the tenant password reset OTP.', 'Password Reset', ['body' => ['subdomain' => 'al-noor', 'email' => 'owner@al-noor.test', 'otp' => '1234', 'change_password_token' => 'token']]],
            ['POST', '/api/v1/tenant/reset-password', 'Reset Tenant Password', 'Set a new tenant-member password.', 'Password Reset', ['body' => ['subdomain' => 'al-noor', 'email' => 'owner@al-noor.test', 'otp' => '1234', 'change_password_token' => 'token', 'password' => 'P@ssw0rd123', 'password_confirmation' => 'P@ssw0rd123']]],
            ['GET', '/api/v1/tenant/team/invitations', 'List Team Invitations', 'List team invitations for the workspace.', 'Team', ['auth' => true]],
            ['POST', '/api/v1/tenant/team/invitations', 'Invite Team Member', 'Invite a member by email and assign a role.', 'Team', ['auth' => true, 'body' => ['email' => 'invitee@example.test', 'name' => 'Invitee', 'role_id' => 5]]],
            ['POST', '/api/v1/tenant/team/invitations/accept', 'Accept Team Invitation', 'Accept an invitation and create the member account.', 'Team', ['body' => ['token' => 'invitation-token', 'name' => 'Invitee', 'password' => 'P@ssw0rd123', 'password_confirmation' => 'P@ssw0rd123']]],
            ['GET', '/api/v1/tenant/team/trash', 'List Deleted Team Members', 'List soft-deleted team members.', 'Team', ['auth' => true]],
            ['PATCH', '/api/v1/tenant/team/{member}/restore', 'Restore Team Member', 'Restore a soft-deleted team member.', 'Team', ['auth' => true]],
            ['DELETE', '/api/v1/tenant/team/{member}/force-delete', 'Force Delete Team Member', 'Permanently remove a soft-deleted team member.', 'Team', ['auth' => true]],
            ['GET', '/api/v1/tenant/roles/permissions', 'List Tenant Role Permissions', 'List the permissions allowed for the authenticated tenant type.', 'Roles', ['auth' => true]],
            ['POST', '/api/v1/tenant/roles', 'Create Tenant Role', 'Create a custom role with allowed permissions.', 'Roles', ['auth' => true, 'body' => ['name' => 'Event Coordinator', 'permission_ids' => [1, 2]]]],
            ['GET', '/api/v1/tenant/roles/{role}', 'Get Tenant Role', 'View a tenant role.', 'Roles', ['auth' => true]],
            ['PUT', '/api/v1/tenant/roles/{role}', 'Update Tenant Role', 'Update a custom role and its permissions.', 'Roles', ['auth' => true]],
            ['DELETE', '/api/v1/tenant/roles/{role}', 'Delete Tenant Role', 'Delete a custom role.', 'Roles', ['auth' => true]],
            ['GET', '/api/v1/tenant/resolve', 'Resolve Tenant Workspace', 'Resolve an active workspace by subdomain and return its encrypted identifier.', 'Authentication', ['query' => ['subdomain' => 'al-noor']]],
            // Phase 3 group application review and notifications
            ['GET', '/api/v1/dashboard/group-applications', 'List Group Applications', 'List, search, and filter group applications.', 'Group Applications', ['auth' => true, 'query' => ['search' => 'mask', 'status' => 'pending_review', 'page' => 1]]],
            ['GET', '/api/v1/dashboard/group-applications/statuses', 'List Application Statuses', 'Return the available group application statuses.', 'Group Applications', ['auth' => true]],
            ['GET', '/api/v1/dashboard/group-applications/{application}', 'Get Group Application', 'View a group application with documents and review history.', 'Group Applications', ['auth' => true]],
            ['GET', '/api/v1/dashboard/group-applications/{application}/documents/{document}/download', 'Download Application Document', 'Download a private application document.', 'Group Applications', ['auth' => true, 'query' => ['inline' => 1]]],
            ['POST', '/api/v1/dashboard/group-applications/{application}/documents/{document}/accept', 'Accept Application Document', 'Accept an application document.', 'Group Applications', ['auth' => true]],
            ['POST', '/api/v1/dashboard/group-applications/{application}/documents/{document}/reject', 'Reject Application Document', 'Reject a document with a reason.', 'Group Applications', ['auth' => true, 'body' => ['reason' => 'The permit is unreadable.']]],
            ['POST', '/api/v1/dashboard/group-applications/{application}/documents/{document}/request-resubmission', 'Request Document Resubmission', 'Request a replacement document with feedback.', 'Group Applications', ['auth' => true, 'body' => ['feedback' => 'Please upload a clearer permit.']]],
            ['POST', '/api/v1/dashboard/group-applications/{application}/approve', 'Approve Group Application', 'Approve an application and activate the group workspace.', 'Group Applications', ['auth' => true]],
            ['POST', '/api/v1/dashboard/group-applications/{application}/reject', 'Reject Group Application', 'Reject an application with a reason and feedback.', 'Group Applications', ['auth' => true, 'body' => ['reason' => 'The permit is invalid.', 'review_feedback' => 'Please contact support.']]],
            ['POST', '/api/v1/dashboard/group-applications/{application}/reopen', 'Reopen Group Application', 'Reopen a rejected application for review.', 'Group Applications', ['auth' => true]],
            ['POST', '/api/v1/dashboard/group-applications/{application}/notes', 'Add Group Application Note', 'Add an internal note to an application.', 'Group Applications', ['auth' => true, 'body' => ['note' => 'Follow up with the group owner.']]],
            ['PATCH', '/api/v1/dashboard/group-applications/{application}/suspend', 'Suspend Group Workspace', 'Suspend an approved group workspace.', 'Group Applications', ['auth' => true, 'body' => ['reason' => 'Policy review.']]],
            ['PATCH', '/api/v1/dashboard/group-applications/{application}/reactivate', 'Reactivate Group Workspace', 'Reactivate a suspended group workspace.', 'Group Applications', ['auth' => true]],
            ['GET', '/api/v1/group/application/documents/{document}/download', 'Download Own Application Document', 'Download an own group application document.', 'Application', ['auth' => true, 'query' => ['inline' => 1]]],
            ['POST', '/api/v1/group/application/documents/{document}/resubmit', 'Resubmit Application Document', 'Upload a replacement for a returned document.', 'Application', ['auth' => true]],
            ['GET', '/api/v1/notifications', 'List Notifications', 'List in-app notifications for the authenticated user.', 'Notifications', ['auth' => true]],
            ['PATCH', '/api/v1/notifications/{notification}/read', 'Mark Notification Read', 'Mark one notification as read.', 'Notifications', ['auth' => true]],
            ['POST', '/api/v1/notifications/read-all', 'Mark All Notifications Read', 'Mark all notifications as read.', 'Notifications', ['auth' => true]],
            // Phase 4 theater operations
            ['GET', '/api/v1/theater/dashboard/profile', 'Get Theater Profile', 'View the authenticated theater profile.', 'Theater Profile', ['auth' => true]],
            ['PUT', '/api/v1/theater/dashboard/profile', 'Update Theater Profile', 'Update theater details, location, timezone, logo, or cover.', 'Theater Profile', ['auth' => true]],
            ['GET', '/api/v1/theater/dashboard/halls', 'List Halls', 'List theater halls with optional search and active filter.', 'Halls', ['auth' => true, 'query' => ['search' => 'main', 'page' => 1]]],
            ['POST', '/api/v1/theater/dashboard/halls', 'Create Hall', 'Create a hall for the authenticated theater.', 'Halls', ['auth' => true, 'body' => ['name' => 'Main Hall', 'description' => 'Main performance space.', 'capacity' => 120]]],
            ['GET', '/api/v1/theater/dashboard/halls/{hall}', 'Get Hall', 'View a theater hall.', 'Halls', ['auth' => true]],
            ['PUT', '/api/v1/theater/dashboard/halls/{hall}', 'Update Hall', 'Update a theater hall.', 'Halls', ['auth' => true, 'body' => ['name' => 'Main Hall', 'capacity' => 140]]],
            ['DELETE', '/api/v1/theater/dashboard/halls/{hall}', 'Delete Hall', 'Soft-delete a hall with no future shows.', 'Halls', ['auth' => true]],
            ['GET', '/api/v1/theater/dashboard/halls/{hall}/schedules', 'List Hall Schedules', 'List weekly schedules for a hall.', 'Hall Schedules', ['auth' => true]],
            ['POST', '/api/v1/theater/dashboard/halls/{hall}/schedules', 'Create Hall Schedule', 'Create a weekly schedule and generate slots.', 'Hall Schedules', ['auth' => true, 'body' => ['day_of_week' => 1, 'starts_at' => '10:00', 'ends_at' => '12:00', 'slot_duration_minutes' => 60, 'price' => 500, 'currency' => 'SAR']]],
            ['PUT', '/api/v1/theater/dashboard/schedules/{schedule}', 'Update Hall Schedule', 'Update a weekly schedule and regenerate slots.', 'Hall Schedules', ['auth' => true]],
            ['DELETE', '/api/v1/theater/dashboard/schedules/{schedule}', 'Delete Hall Schedule', 'Remove a weekly schedule.', 'Hall Schedules', ['auth' => true]],
            ['GET', '/api/v1/theater/dashboard/halls/{hall}/slots', 'List Hall Slots', 'List generated availability slots with date and status filters.', 'Hall Availability', ['auth' => true, 'query' => ['from' => '2026-12-01', 'to' => '2026-12-31', 'status' => 'available']]],
            ['POST', '/api/v1/theater/dashboard/halls/{hall}/slots/generate', 'Generate Hall Slots', 'Regenerate materialized slots for the rolling horizon.', 'Hall Availability', ['auth' => true]],
            ['GET', '/api/v1/theater/dashboard/halls/{hall}/blocks', 'List Hall Blocks', 'List blocked hall periods.', 'Hall Availability', ['auth' => true]],
            ['POST', '/api/v1/theater/dashboard/halls/{hall}/blocks', 'Block Hall Period', 'Block a hall period and its available slots.', 'Hall Availability', ['auth' => true, 'body' => ['starts_at' => '2026-12-01T10:00:00+03:00', 'ends_at' => '2026-12-01T11:00:00+03:00', 'reason' => 'Maintenance']]],
            ['DELETE', '/api/v1/theater/dashboard/blocks/{block}', 'Delete Hall Block', 'Remove a block and restore available slots.', 'Hall Availability', ['auth' => true]],
            ['GET', '/api/v1/theater/dashboard/events', 'List Theater Events', 'List theater events with search and status filter.', 'Events', ['auth' => true, 'query' => ['search' => 'night', 'status' => 'draft']]],
            ['POST', '/api/v1/theater/dashboard/events', 'Create Event', 'Create an event with English and Arabic content and a poster.', 'Events', ['auth' => true, 'body' => ['title_en' => 'The Night Show', 'title_ar' => 'عرض الليل', 'description_en' => 'An evening performance.', 'category_id' => 1]]],
            ['GET', '/api/v1/theater/dashboard/events/{event}', 'Get Theater Event', 'View an event with shows and ticket tiers.', 'Events', ['auth' => true]],
            ['PUT', '/api/v1/theater/dashboard/events/{event}', 'Update Theater Event', 'Update event content, category, slug, or poster.', 'Events', ['auth' => true]],
            ['POST', '/api/v1/theater/dashboard/events/{event}/publish', 'Publish Event', 'Publish an event with upcoming shows.', 'Events', ['auth' => true]],
            ['POST', '/api/v1/theater/dashboard/events/{event}/unpublish', 'Unpublish Event', 'Return a published event to draft.', 'Events', ['auth' => true]],
            ['POST', '/api/v1/theater/dashboard/events/{event}/cancel', 'Cancel Event', 'Cancel an event and its scheduled shows.', 'Events', ['auth' => true]],
            ['GET', '/api/v1/theater/dashboard/events/{event}/shows', 'List Event Shows', 'List the shows for an event.', 'Shows', ['auth' => true]],
            ['POST', '/api/v1/theater/dashboard/events/{event}/shows', 'Create Show', 'Schedule a show in one of the theater halls.', 'Shows', ['auth' => true, 'body' => ['hall_id' => 1, 'starts_at' => '2026-12-10T18:00:00+03:00', 'ends_at' => '2026-12-10T20:00:00+03:00']]],
            ['GET', '/api/v1/theater/dashboard/shows/{show}', 'Get Show', 'View a show with hall and ticket tiers.', 'Shows', ['auth' => true]],
            ['PUT', '/api/v1/theater/dashboard/shows/{show}', 'Update Show', 'Reschedule a show with overlap validation.', 'Shows', ['auth' => true]],
            ['POST', '/api/v1/theater/dashboard/shows/{show}/cancel', 'Cancel Show', 'Cancel a show and release its slots.', 'Shows', ['auth' => true]],
            ['GET', '/api/v1/theater/dashboard/shows/{show}/tiers', 'List Ticket Tiers', 'List the ticket tiers for a show.', 'Ticket Tiers', ['auth' => true]],
            ['POST', '/api/v1/theater/dashboard/shows/{show}/tiers', 'Create Ticket Tier', 'Create a ticket tier with capacity and sale window.', 'Ticket Tiers', ['auth' => true, 'body' => ['name_en' => 'General Admission', 'name_ar' => 'دخول عام', 'price' => 100, 'currency' => 'SAR', 'total_quantity' => 50]]],
            ['PUT', '/api/v1/theater/dashboard/tiers/{tier}', 'Update Ticket Tier', 'Update a ticket tier.', 'Ticket Tiers', ['auth' => true]],
            ['DELETE', '/api/v1/theater/dashboard/tiers/{tier}', 'Deactivate Ticket Tier', 'Deactivate a ticket tier.', 'Ticket Tiers', ['auth' => true]],
            ['GET', '/api/v1/public/theaters', 'List Public Theaters', 'List active theaters with public profile fields.', 'Public Catalog', []] ,
            ['GET', '/api/v1/public/theaters/{theater}', 'Get Public Theater', 'View a public theater profile and active halls.', 'Public Catalog', []] ,
            ['GET', '/api/v1/public/theaters/{theater}/halls', 'List Public Theater Halls', 'List active halls for a public theater.', 'Public Catalog', []] ,
            ['GET', '/api/v1/public/halls/{hall}/availability', 'Get Public Hall Availability', 'Return requestable slots for a hall date range.', 'Public Catalog', ['query' => ['from' => '2026-12-01', 'to' => '2026-12-31']]],
            ['GET', '/api/v1/public/events', 'List Public Events', 'List published events with search and filters.', 'Public Catalog', ['query' => ['search' => 'night', 'category' => 'theater', 'date' => '2026-12-10']]],
            ['GET', '/api/v1/public/events/{slug}', 'Get Public Event', 'View a published event with upcoming shows and tiers.', 'Public Catalog', []] ,
            ['GET', '/api/v1/public/event-categories', 'List Event Categories', 'List active public event categories.', 'Public Catalog', []] ,
            // Security utilities
            ['GET', '/api/v1/security/key-info', 'Get API Key Info', 'Return API client key status and expiry.', 'Security', ['auth' => true]],
            ['POST', '/api/v1/security/renew-key', 'Renew API Key', 'Rotate the API client key.', 'Security', ['auth' => true]],
            ['GET', '/api/v1/security/nonce', 'Get Security Nonce', 'Issue a one-time nonce for anti-replay protection.', 'Security', []],
        ];

        foreach ($endpoints as [$method, $route, $name, $short, $group, $options]) {
            $this->add($method, $route, $name, $short, $group, $options);
        }
    }

    private function add(string $method, string $route, string $name, string $short, string $group, array $options): void
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($options['auth'] ?? false) {
            $headers = ['Authorization' => 'Bearer {token}', 'Content-Type' => 'application/json'];
        }

        Documentations::updateOrCreate(
            ['method' => $method, 'route' => $route],
            [
                'name' => $name,
                'section' => 1,
                'category' => Str::slug($method.' '.$route),
                'group' => $group,
                'sorting' => $this->sorting++,
                'short_description' => $short,
                'description' => $short,
                'headers' => json_encode($headers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'notes' => null,
                'query_params' => json_encode($options['query'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'body' => json_encode($options['body'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'method' => $method,
                'route' => $route,
                'success_response' => json_encode([
                    'success' => true,
                    'message' => 'Operation completed successfully.',
                    'data' => [],
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'error_response' => json_encode([
                    'success' => false,
                    'message' => 'The request could not be completed.',
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'developer_tested' => 1,
                'tester_tested' => 1,
                'is_active' => 1,
            ]
        );
    }
}
