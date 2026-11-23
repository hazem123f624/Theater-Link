<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservations\Http\Controllers\GroupDiscoveryController;
use Modules\Reservations\Http\Controllers\GroupProfileController;
use Modules\Reservations\Http\Controllers\GroupReservationController;
use Modules\Reservations\Http\Controllers\TheaterReservationController;

Route::middleware(['security', 'auth:sanctum', 'abilities:tenant_members', 'tenant', 'tenant.active', 'tenant.type:group'])
    ->prefix('v1/group/dashboard')
    ->group(function (): void {
        Route::get('profile', [GroupProfileController::class, 'show'])
            ->middleware('permission:group.profile.read')
            ->name('group.profile.show');
        Route::put('profile', [GroupProfileController::class, 'update'])
            ->middleware('permission:group.profile.update')
            ->name('group.profile.update');

        Route::get('theaters', [GroupDiscoveryController::class, 'theaters'])
            ->middleware('permission:reservations.fetch')
            ->name('group.theaters.index');
        Route::get('theaters/{theater}', [GroupDiscoveryController::class, 'theater'])
            ->middleware('permission:reservations.fetch')
            ->name('group.theaters.show');
        Route::get('theaters/{theater}/halls', [GroupDiscoveryController::class, 'halls'])
            ->middleware('permission:reservations.fetch')
            ->name('group.theaters.halls');
        Route::get('halls/{hall}/availability', [GroupDiscoveryController::class, 'availability'])
            ->middleware('permission:reservations.fetch')
            ->name('group.halls.availability');

        Route::get('reservations', [GroupReservationController::class, 'index'])
            ->middleware('permission:reservations.fetch')
            ->name('group.reservations.index');
        Route::post('reservations', [GroupReservationController::class, 'store'])
            ->middleware('permission:reservations.create')
            ->name('group.reservations.store');
        Route::get('reservations/{reservation}', [GroupReservationController::class, 'show'])
            ->middleware('permission:reservations.read')
            ->name('group.reservations.show');
        Route::post('reservations/{reservation}/cancel', [GroupReservationController::class, 'cancel'])
            ->middleware('permission:reservations.cancel')
            ->name('group.reservations.cancel');
    });

Route::middleware(['security', 'auth:sanctum', 'abilities:tenant_members', 'tenant', 'tenant.active', 'tenant.type:theater'])
    ->prefix('v1/theater/dashboard')
    ->group(function (): void {
        Route::get('reservations', [TheaterReservationController::class, 'index'])
            ->middleware('permission:reservations.fetch')
            ->name('theater.reservations.index');
        Route::get('reservations/{reservation}', [TheaterReservationController::class, 'show'])
            ->middleware('permission:reservations.read')
            ->name('theater.reservations.show');
        Route::post('reservations/{reservation}/approve', [TheaterReservationController::class, 'approve'])
            ->middleware('permission:reservations.approve')
            ->name('theater.reservations.approve');
        Route::post('reservations/{reservation}/reject', [TheaterReservationController::class, 'reject'])
            ->middleware('permission:reservations.reject')
            ->name('theater.reservations.reject');
        Route::post('reservations/{reservation}/cancel', [TheaterReservationController::class, 'cancel'])
            ->middleware('permission:reservations.cancel')
            ->name('theater.reservations.cancel');
    });
