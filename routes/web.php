<?php

use App\Http\Controllers\ApiDocumentationController;
use App\Http\Middleware\BlockInProduction;
use Illuminate\Support\Facades\Route;

Route::middleware(BlockInProduction::class)->group(function (): void {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/docs', [ApiDocumentationController::class, 'index'])->name('api-docs-home');
});
