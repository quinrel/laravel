<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Quinrel\Laravel\Http\Controllers\Api\AnalyzeContentController;
use Quinrel\Laravel\Http\Controllers\DashboardController;
use Quinrel\Laravel\Http\Controllers\IndexNowKeyController;
use Quinrel\Laravel\Http\Controllers\LlmsTxtController;

if (config('quinrel.llms_txt.enabled', true)) {
    Route::get('/llms.txt', [LlmsTxtController::class, 'index'])
        ->name('quinrel.root.llms.txt');

    Route::get('/llms-full.txt', [LlmsTxtController::class, 'full'])
        ->name('quinrel.root.llms.full.txt');
}

Route::prefix(config('quinrel.ui.path', 'quinrel'))
    ->middleware(config('quinrel.ui.middleware', ['web']))
    ->group(function () {
        Route::post('/api/analyze', AnalyzeContentController::class)
            ->name('quinrel.api.analyze');

        Route::post('/api/setup', [DashboardController::class, 'saveSetup'])
            ->name('quinrel.api.setup');

        Route::get('/llms.txt', [LlmsTxtController::class, 'index'])
            ->name('quinrel.llms.txt');

        Route::get('/llms-full.txt', [LlmsTxtController::class, 'full'])
            ->name('quinrel.llms.full.txt');

        if (config('quinrel.ui.driver') === 'standalone') {
            Route::get('/{view?}', [DashboardController::class, 'index'])
                ->where('view', '^(?!api/).*')
                ->name('quinrel.dashboard');
        }
    });

if (config('quinrel.indexnow.enabled') && config('quinrel.indexnow.key')) {
    Route::get('/'.config('quinrel.indexnow.key').'.txt', IndexNowKeyController::class)
        ->name('quinrel.indexnow.key');
}
