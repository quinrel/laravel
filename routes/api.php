<?php

declare(strict_types=1);

use Holvek\Laravel\Http\Controllers\HolvekRumController;
use Illuminate\Support\Facades\Route;

Route::prefix('holvek/api/v1')->middleware(['api'])->group(function () {
    Route::post('/rum', [HolvekRumController::class, 'store'])
        ->middleware('throttle:600,1')
        ->name('holvek.api.rum.store');
});
