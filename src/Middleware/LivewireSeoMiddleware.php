<?php

declare(strict_types=1);

namespace Holvek\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LivewireSeoMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // If Livewire is present, we could hook into component lifecycle,
        // but for middleware, we just return response.
        return $response;
    }
}
