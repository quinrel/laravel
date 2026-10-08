<?php

declare(strict_types=1);

namespace Holvek\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class InertiaSeoMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Share the SEO state via Inertia sharing if the Inertia package exists
        if (class_exists(Inertia::class)) {
            Inertia::share('holvek_seo', function () {
                // Return SEO payload to the frontend. Note: Requires resolving the model
                // for the current route, or passing global SEO context.
                return [
                    'title' => config('app.name'),
                    'description' => '',
                ];
            });
        }

        return $response;
    }
}
