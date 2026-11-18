<?php

use App\Http\Controllers\ApiDocumentationController;
use App\Http\Middleware\BlockInProduction;
use Illuminate\Support\Facades\Route;

Route::middleware(BlockInProduction::class)->group(function (): void {
    Route::get('/docs', [ApiDocumentationController::class, 'index'])->name('api-docs-home');
});

Route::get('/', fn () => view('app'));

// Serve the single-page application for all non-API, non-docs routes.
Route::get('/{any?}', fn () => view('app'))
    ->where('any', '^(?!api(?:/|$)|docs(?:/|$)|up$|storage(?:/|$)|build(?:/|$)).*$');
