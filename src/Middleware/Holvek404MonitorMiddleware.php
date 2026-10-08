<?php

declare(strict_types=1);

namespace Holvek\Laravel\Middleware;

use Closure;
use Holvek\Laravel\Models\Holvek404Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Holvek404MonitorMiddleware
{
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        if ($response->getStatusCode() === 404) {
            $path = '/'.ltrim($request->path(), '/');
            $referrer = $request->headers->get('referer');

            // UPSERT into 404 logs
            Holvek404Log::upsert(
                [
                    'path' => $path,
                    'hits' => 1,
                    'last_referrer' => $referrer,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                ['path'],
                ['hits' => DB::raw('hits + 1'), 'last_referrer' => DB::raw('VALUES(last_referrer)'), 'updated_at' => now()]
            );
        }

        return $response;
    }
}
