<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Quinrel\Laravel\Services\HeadRenderer;
use Symfony\Component\HttpFoundation\Response;

class InertiaSeoMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Share the SEO state via Inertia BEFORE calling $next($request) so Inertia responses include it
        if (class_exists(Inertia::class)) {
            Inertia::share('quinrel_seo', fn () => app(HeadRenderer::class)->resolveMetadata());
        }

        return $next($request);
    }
}
