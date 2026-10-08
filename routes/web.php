<?php

declare(strict_types=1);

use Holvek\Laravel\Http\Controllers\Api\AnalyzeContentController;
use Holvek\Laravel\Http\Controllers\LlmsTxtController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('holvek.path', 'holvek'))
    ->middleware(config('holvek.middleware', ['web']))
    ->group(function () {
        Route::post('/api/analyze', AnalyzeContentController::class)
            ->name('holvek.api.analyze');

        Route::get('/llms.txt', [LlmsTxtController::class, 'index'])
            ->name('holvek.llms.txt');

        Route::get('/llms-full.txt', [LlmsTxtController::class, 'full'])
            ->name('holvek.llms.full.txt');

        Route::get('/{view?}', function () {
            return view('holvek::app');
        })->where('view', '(.*)')->name('holvek.dashboard');
    });
