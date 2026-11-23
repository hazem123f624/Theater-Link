<?php

use Illuminate\Support\Facades\Route;
use Modules\Bookings\Http\Controllers\PublicShowTierController;
use Modules\Bookings\Http\Controllers\TheaterBookingController;
use Modules\Bookings\Http\Controllers\TheaterTicketController;
use Modules\Bookings\Http\Controllers\UserBookingController;

Route::middleware(['security'])
    ->prefix('v1/public')
    ->group(function (): void {
        Route::get('shows/{show}/tiers', [PublicShowTierController::class, 'show'])
            ->name('public.shows.tiers');
    });

Route::middleware(['security', 'auth:sanctum', 'abilities:users'])
    ->prefix('v1/user/bookings')
    ->group(function (): void {
        Route::get('/', [UserBookingController::class, 'index'])->name('user.bookings.index');
        Route::post('/', [UserBookingController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('user.bookings.store');
        Route::get('{booking}', [UserBookingController::class, 'show'])->name('user.bookings.show');
        Route::post('{booking}/cancel', [UserBookingController::class, 'cancel'])->name('user.bookings.cancel');
    });

Route::middleware(['security', 'auth:sanctum', 'abilities:tenant_members', 'tenant', 'tenant.active', 'tenant.type:theater'])
    ->prefix('v1/theater/dashboard')
    ->group(function (): void {
        Route::get('bookings', [TheaterBookingController::class, 'index'])
            ->middleware('permission:bookings.fetch')
            ->name('theater.bookings.index');
        Route::get('bookings/{booking}', [TheaterBookingController::class, 'show'])
            ->middleware('permission:bookings.read')
            ->name('theater.bookings.show');
        Route::post('bookings/{booking}/cancel', [TheaterBookingController::class, 'cancel'])
            ->middleware('permission:bookings.cancel')
            ->name('theater.bookings.cancel');
        Route::post('tickets/validate', [TheaterTicketController::class, 'validate'])
            ->middleware('permission:tickets.validate')
            ->name('theater.tickets.validate');
    });
