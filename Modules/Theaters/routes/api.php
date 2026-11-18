<?php

use Illuminate\Support\Facades\Route;
use Modules\Theaters\Http\Controllers\EventController;
use Modules\Theaters\Http\Controllers\HallController;
use Modules\Theaters\Http\Controllers\HallScheduleController;
use Modules\Theaters\Http\Controllers\HallSlotController;
use Modules\Theaters\Http\Controllers\PublicCatalogController;
use Modules\Theaters\Http\Controllers\ShowController;
use Modules\Theaters\Http\Controllers\TheaterProfileController;
use Modules\Theaters\Http\Controllers\TicketTierController;

Route::middleware(['security-public'])->prefix('v1/public')->group(function (): void {
    Route::get('theaters', [PublicCatalogController::class, 'theaters'])->name('public.theaters.index');
    Route::get('theaters/{theater}', [PublicCatalogController::class, 'theater'])->whereNumber('theater')->name('public.theaters.show');
    Route::get('theaters/{theater}/halls', [PublicCatalogController::class, 'halls'])->whereNumber('theater')->name('public.theaters.halls');
    Route::get('halls/{hall}/availability', [PublicCatalogController::class, 'availability'])->whereNumber('hall')->name('public.halls.availability');
    Route::get('events', [PublicCatalogController::class, 'events'])->name('public.events.index');
    Route::get('events/{slug}', [PublicCatalogController::class, 'event'])->name('public.events.show');
    Route::get('event-categories', [PublicCatalogController::class, 'categories'])->name('public.event-categories.index');
});

Route::middleware(['security', 'auth:sanctum', 'abilities:tenant_members', 'tenant', 'tenant.active', 'tenant.type:theater'])
    ->prefix('v1/theater/dashboard')
    ->group(function (): void {
        Route::get('profile', [TheaterProfileController::class, 'show'])
            ->middleware('permission:theater.profile.read')
            ->name('theater.profile.show');
        Route::put('profile', [TheaterProfileController::class, 'update'])
            ->middleware('permission:theater.profile.update')
            ->name('theater.profile.update');

        Route::get('halls', [HallController::class, 'index'])
            ->middleware('permission:halls.fetch')
            ->name('theater.halls.index');
        Route::post('halls', [HallController::class, 'store'])
            ->middleware('permission:halls.create')
            ->name('theater.halls.store');
        Route::get('halls/{hall}', [HallController::class, 'show'])
            ->middleware('permission:halls.fetch')
            ->name('theater.halls.show');
        Route::put('halls/{hall}', [HallController::class, 'update'])
            ->middleware('permission:halls.update')
            ->name('theater.halls.update');
        Route::delete('halls/{hall}', [HallController::class, 'destroy'])
            ->middleware('permission:halls.delete')
            ->name('theater.halls.destroy');

        Route::get('halls/{hall}/schedules', [HallScheduleController::class, 'index'])
            ->middleware('permission:hall_schedules.fetch')
            ->name('theater.halls.schedules.index');
        Route::post('halls/{hall}/schedules', [HallScheduleController::class, 'store'])
            ->middleware('permission:hall_schedules.create')
            ->name('theater.halls.schedules.store');
        Route::put('schedules/{schedule}', [HallScheduleController::class, 'update'])
            ->middleware('permission:hall_schedules.update')
            ->name('theater.schedules.update');
        Route::delete('schedules/{schedule}', [HallScheduleController::class, 'destroy'])
            ->middleware('permission:hall_schedules.delete')
            ->name('theater.schedules.destroy');

        Route::get('halls/{hall}/slots', [HallSlotController::class, 'index'])
            ->middleware('permission:hall_schedules.fetch')
            ->name('theater.halls.slots.index');
        Route::post('halls/{hall}/slots/generate', [HallSlotController::class, 'generate'])
            ->middleware('permission:hall_schedules.create')
            ->name('theater.halls.slots.generate');
        Route::get('halls/{hall}/blocks', [HallSlotController::class, 'blocks'])
            ->middleware('permission:hall_schedules.fetch')
            ->name('theater.halls.blocks.index');
        Route::post('halls/{hall}/blocks', [HallSlotController::class, 'block'])
            ->middleware('permission:hall_schedules.create')
            ->name('theater.halls.blocks.store');
        Route::delete('blocks/{block}', [HallSlotController::class, 'destroyBlock'])
            ->middleware('permission:hall_schedules.delete')
            ->name('theater.blocks.destroy');

        Route::get('events', [EventController::class, 'index'])
            ->middleware('permission:events.fetch')
            ->name('theater.events.index');
        Route::post('events', [EventController::class, 'store'])
            ->middleware('permission:events.create')
            ->name('theater.events.store');
        Route::get('events/{event}', [EventController::class, 'show'])
            ->middleware('permission:events.fetch')
            ->name('theater.events.show');
        Route::put('events/{event}', [EventController::class, 'update'])
            ->middleware('permission:events.update')
            ->name('theater.events.update');
        Route::post('events/{event}/publish', [EventController::class, 'publish'])
            ->middleware('permission:events.publish')
            ->name('theater.events.publish');
        Route::post('events/{event}/unpublish', [EventController::class, 'unpublish'])
            ->middleware('permission:events.publish')
            ->name('theater.events.unpublish');
        Route::post('events/{event}/cancel', [EventController::class, 'cancel'])
            ->middleware('permission:events.update')
            ->name('theater.events.cancel');

        Route::get('events/{event}/shows', [ShowController::class, 'index'])
            ->middleware('permission:events.fetch')
            ->name('theater.events.shows.index');
        Route::post('events/{event}/shows', [ShowController::class, 'store'])
            ->middleware('permission:events.update')
            ->name('theater.events.shows.store');
        Route::get('shows/{show}', [ShowController::class, 'show'])
            ->middleware('permission:events.fetch')
            ->name('theater.shows.show');
        Route::put('shows/{show}', [ShowController::class, 'update'])
            ->middleware('permission:events.update')
            ->name('theater.shows.update');
        Route::post('shows/{show}/cancel', [ShowController::class, 'cancel'])
            ->middleware('permission:events.update')
            ->name('theater.shows.cancel');

        Route::get('shows/{show}/tiers', [TicketTierController::class, 'index'])
            ->middleware('permission:ticket_tiers.fetch')
            ->name('theater.shows.tiers.index');
        Route::post('shows/{show}/tiers', [TicketTierController::class, 'store'])
            ->middleware('permission:ticket_tiers.create')
            ->name('theater.shows.tiers.store');
        Route::put('tiers/{tier}', [TicketTierController::class, 'update'])
            ->middleware('permission:ticket_tiers.update')
            ->name('theater.tiers.update');
        Route::delete('tiers/{tier}', [TicketTierController::class, 'destroy'])
            ->middleware('permission:ticket_tiers.delete')
            ->name('theater.tiers.destroy');
    });
