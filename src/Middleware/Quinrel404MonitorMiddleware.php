<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Quinrel\Laravel\Models\Quinrel404Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Quinrel404MonitorMiddleware
{
    /** @var array<int, string> */
    protected array $ignoredExtensions = [
        'map', 'ico', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'css', 'js', 'woff', 'woff2',
    ];

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        if ($response->getStatusCode() === 404) {
            $url = mb_substr('/'.ltrim($request->path(), '/'), 0, 255);
            $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

            if ($extension !== '' && in_array($extension, $this->ignoredExtensions, true)) {
                return $response;
            }

            $referrerHeader = $request->headers->get('referer');
            $referrer = is_string($referrerHeader) && $referrerHeader !== ''
                ? mb_substr($referrerHeader, 0, 255)
                : null;

            $now = now();

            Quinrel404Log::upsert(
                [
                    [
                        'url' => $url,
                        'referrer' => $referrer,
                        'hits' => 1,
                        'last_hit_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ],
                ['url'],
                [
                    'hits' => DB::raw('hits + 1'),
                    'referrer' => $referrer ?? DB::raw('referrer'),
                    'last_hit_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        return $response;
    }
}
