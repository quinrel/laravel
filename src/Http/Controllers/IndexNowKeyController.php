<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers;

use Illuminate\Http\Response;

class IndexNowKeyController
{
    public function __invoke(): Response
    {
        $key = (string) (config('quinrel.indexnow.key') ?: config('quinrel.api.indexnow_key', ''));

        if ($key === '') {
            abort(404);
        }

        return response($key, 200)
            ->header('Content-Type', 'text/plain');
    }
}
