<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Quinrel\Laravel\Http\Controllers\Api\AuditWebhookController;
use Quinrel\Laravel\Http\Controllers\Api\DiscoveryScanController;
use Quinrel\Laravel\Http\Controllers\Api\HeadlessSeoController;
use Quinrel\Laravel\Http\Controllers\QuinrelRumController;

foreach (['quinrel/api/v1', 'quinrel/v1'] as $prefix) {
    Route::prefix($prefix)->middleware(['api'])->group(function () use ($prefix) {
        $isCanonical = ($prefix === 'quinrel/api/v1');

        $rum = Route::post('/rum', [QuinrelRumController::class, 'store'])
            ->middleware('throttle:600,1');
        if ($isCanonical) {
            $rum->name('quinrel.api.rum.store');
        }

        $global = Route::get('/seo/global', [HeadlessSeoController::class, 'global']);
        if ($isCanonical) {
            $global->name('quinrel.api.seo.global');
        }

        $show = Route::get('/seo/{id}', [HeadlessSeoController::class, 'show'])
            ->whereNumber('id');
        if ($isCanonical) {
            $show->name('quinrel.api.seo.show');
        }

        $scan = Route::get('/discovery/scan', [DiscoveryScanController::class, 'scan'])
            ->middleware('throttle:60,1');
        if ($isCanonical) {
            $scan->name('quinrel.api.discovery.scan');
        }

        $audit = Route::post('/webhooks/audit', AuditWebhookController::class);
        if ($isCanonical) {
            $audit->name('quinrel.webhooks.audit');
        }
    });
}
