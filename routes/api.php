<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('holvek/v1')->middleware('api')->group(function () {
    // Headless REST API endpoints will be registered here.
});
