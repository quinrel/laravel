<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Quinrel\Laravel\Http\Controllers\Api\AuditWebhookController;
use Quinrel\Laravel\Http\Controllers\Api\DiscoveryScanController;
use Quinrel\Laravel\Http\Controllers\Api\HeadlessSeoController;
use Quinrel\Laravel\Http\Controllers\QuinrelRumController;

Route::prefix('quinrel/api/v1')->middleware(['api'])->group(function () {
    Route::post('/rum', [QuinrelRumController::class, 'store'])
        ->middleware('throttle:600,1')
        ->name('quinrel.api.rum.store');

    Route::get('/seo/global', [HeadlessSeoController::class, 'global'])
        ->name('quinrel.api.seo.global');

    Route::get('/seo/{id}', [HeadlessSeoController::class, 'show'])
        ->whereNumber('id')
        ->name('quinrel.api.seo.show');

    Route::get('/discovery/scan', [DiscoveryScanController::class, 'scan'])
        ->middleware('throttle:60,1')
        ->name('quinrel.api.discovery.scan');

    Route::post('/webhooks/audit', AuditWebhookController::class)
        ->name('quinrel.webhooks.audit');
});
