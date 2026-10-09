<?php

declare(strict_types=1);

use Holvek\Laravel\Http\Controllers\Api\AnalyzeContentController;
use Holvek\Laravel\Http\Controllers\LlmsTxtController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('holvek.ui.path', config('holvek.path', 'holvek')))
    ->middleware(config('holvek.ui.middleware', config('holvek.middleware', ['web'])))
    ->group(function () {
        Route::post('/api/analyze', AnalyzeContentController::class)
            ->name('holvek.api.analyze');

        Route::get('/llms.txt', [LlmsTxtController::class, 'index'])
            ->name('holvek.llms.txt');

        Route::get('/llms-full.txt', [LlmsTxtController::class, 'full'])
            ->name('holvek.llms.full.txt');

        if (config('holvek.ui.driver') === 'standalone') {
            Route::get('/{view?}', function () {
                return view('holvek::app');
            })->where('view', '(.*)')->name('holvek.dashboard');
        }
    });

if (config('holvek.indexnow.enabled') && config('holvek.indexnow.key')) {
    Route::get('/'.config('holvek.indexnow.key').'.txt', function () {
        return response(config('holvek.indexnow.key'), 200)
            ->header('Content-Type', 'text/plain');
    });
}
