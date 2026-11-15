<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenancy\Http\Controllers\GroupApplicationController;
use Modules\Tenancy\Http\Controllers\TenantAuthController;
use Modules\Tenancy\Http\Controllers\TenantInvitationController;
use Modules\Tenancy\Http\Controllers\TenantDashboardController;
use Modules\Tenancy\Http\Controllers\TenantPasswordResetController;
use Modules\Tenancy\Http\Controllers\TenantRegistrationController;
use Modules\Tenancy\Http\Controllers\TenantRoleController;
use Modules\Tenancy\Http\Controllers\TenantTeamController;

Route::middleware(['security-public'])->prefix('v1')->group(function (): void {
    Route::get('tenant/resolve', [TenantAuthController::class, 'resolve'])->name('tenant.resolve');
    Route::post('theater/login', [TenantAuthController::class, 'loginTheater'])->name('theater.login');
    Route::post('group/login', [TenantAuthController::class, 'loginGroup'])->name('group.login');

    Route::post('theater/register', [TenantRegistrationController::class, 'registerTheater'])->name('theater.register');
    Route::post('theater/verify', [TenantRegistrationController::class, 'verifyTheater'])->name('theater.verify');
    Route::post('theater/resend-otp', [TenantRegistrationController::class, 'resendTheaterOtp'])->name('theater.resend-otp');

    Route::post('group/register', [TenantRegistrationController::class, 'registerGroup'])->name('group.register');
    Route::post('group/verify', [TenantRegistrationController::class, 'verifyGroup'])->name('group.verify');
    Route::post('group/resend-otp', [TenantRegistrationController::class, 'resendGroupOtp'])->name('group.resend-otp');

    Route::post('tenant/team/invitations/accept', [TenantInvitationController::class, 'accept'])->name('tenant.invitations.accept');

    Route::post('tenant/forgot-password', [TenantPasswordResetController::class, 'requestReset'])->name('tenant.forgot-password');
    Route::post('tenant/verify-forgot-password', [TenantPasswordResetController::class, 'verifyOtp'])->name('tenant.verify-forgot-password');
    Route::post('tenant/reset-password', [TenantPasswordResetController::class, 'resetPassword'])->name('tenant.reset-password');
});

Route::middleware(['security', 'auth:sanctum', 'abilities:tenant_members', 'tenant', 'tenant.active', 'tenant.type:theater'])
    ->prefix('v1/theater')
    ->group(function (): void {
        Route::get('dashboard', [TenantDashboardController::class, 'theater'])->name('theater.dashboard');
    });

Route::middleware(['security', 'auth:sanctum', 'abilities:tenant_members', 'tenant', 'tenant.active', 'tenant.type:group'])
    ->prefix('v1/group')
    ->group(function (): void {
        Route::get('dashboard', [TenantDashboardController::class, 'group'])->name('group.dashboard');
    });

// Pending group owners can reach the application status area; workspace routes stay blocked.
Route::middleware(['security', 'auth:sanctum', 'abilities:tenant_members', 'tenant', 'tenant.type:group'])
    ->prefix('v1/group')
    ->group(function (): void {
        Route::get('application', [GroupApplicationController::class, 'show'])->name('group.application.show');
        Route::get('application/documents', [GroupApplicationController::class, 'documents'])->name('group.application.documents');
        Route::get('application/workspace', [GroupApplicationController::class, 'workspace'])->name('group.application.workspace');
        Route::get('application/support', [GroupApplicationController::class, 'support'])->name('group.application.support');
    });

Route::middleware(['security', 'auth:sanctum', 'abilities:tenant_members', 'tenant'])
    ->prefix('v1/tenant')
    ->group(function (): void {
        Route::get('me', [TenantAuthController::class, 'me'])->name('tenant.me');
        Route::post('logout', [TenantAuthController::class, 'logout'])->name('tenant.logout');
        Route::post('refresh-token', [TenantAuthController::class, 'refreshToken'])->name('tenant.refresh-token');

        Route::middleware('tenant.active')->group(function (): void {
            Route::get('team/invitations', [TenantInvitationController::class, 'index'])
                ->middleware('permission:team.fetch')
                ->name('tenant.invitations.index');
            Route::post('team/invitations', [TenantInvitationController::class, 'store'])
                ->middleware('permission:team.create')
                ->name('tenant.invitations.store');

            Route::get('team', [TenantTeamController::class, 'index'])
                ->middleware('permission:team.fetch')
                ->name('tenant.team.index');
            Route::post('team', [TenantTeamController::class, 'store'])
                ->middleware('permission:team.create')
                ->name('tenant.team.store');
            Route::get('team/trash', [TenantTeamController::class, 'trash'])
                ->middleware('permission:team.fetch')
                ->name('tenant.team.trash');
            Route::patch('team/{member}/restore', [TenantTeamController::class, 'restore'])
                ->withTrashed()
                ->middleware('permission:team.update')
                ->name('tenant.team.restore');
            Route::delete('team/{member}/force-delete', [TenantTeamController::class, 'forceDelete'])
                ->withTrashed()
                ->middleware('permission:team.delete')
                ->name('tenant.team.force-delete');
            Route::put('team/{member}', [TenantTeamController::class, 'update'])
                ->middleware('permission:team.update')
                ->name('tenant.team.update');
            Route::patch('team/{member}/toggle-active', [TenantTeamController::class, 'toggleActive'])
                ->middleware('permission:team.update')
                ->name('tenant.team.toggle-active');
            Route::delete('team/{member}', [TenantTeamController::class, 'destroy'])
                ->middleware('permission:team.delete')
                ->name('tenant.team.destroy');

            Route::get('roles', [TenantRoleController::class, 'index'])
                ->middleware('permission:roles.fetch')
                ->name('tenant.roles.index');
            Route::post('roles', [TenantRoleController::class, 'store'])
                ->middleware('permission:roles.create')
                ->name('tenant.roles.store');
            Route::get('roles/{role}', [TenantRoleController::class, 'show'])
                ->middleware('permission:roles.fetch')
                ->name('tenant.roles.show');
            Route::put('roles/{role}', [TenantRoleController::class, 'update'])
                ->middleware('permission:roles.update')
                ->name('tenant.roles.update');
            Route::delete('roles/{role}', [TenantRoleController::class, 'destroy'])
                ->middleware('permission:roles.delete')
                ->name('tenant.roles.destroy');
        });
    });
