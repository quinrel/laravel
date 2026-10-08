<?php

declare(strict_types=1);

namespace Holvek\Laravel\Middleware;

use Closure;
use Holvek\Laravel\Models\HolvekRedirect;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HolvekRedirectMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();
        $fullUrl = $request->fullUrl();

        // Check exact match first
        /** @var HolvekRedirect|null $redirect */
        $redirect = HolvekRedirect::where('is_regex', false)
            ->where(function ($q) use ($path, $fullUrl) {
                $q->where('source_url', $path)
                    ->orWhere('source_url', '/'.ltrim($path, '/'))
                    ->orWhere('source_url', $fullUrl);
            })->first();

        if ($redirect) {
            return redirect($redirect->target_url, $redirect->status_code);
        }

        // Check regex matches
        /** @var Collection<int, HolvekRedirect> $regexRedirects */
        $regexRedirects = HolvekRedirect::where('is_regex', true)->get();

        foreach ($regexRedirects as $regexRedirect) {
            $pattern = '#^'.str_replace('#', '\#', $regexRedirect->source_url).'$#';
            if (preg_match($pattern, $path) || preg_match($pattern, '/'.ltrim($path, '/'))) {
                $target = preg_replace($pattern, $regexRedirect->target_url, $path);
                if (is_string($target)) {
                    return redirect($target, $regexRedirect->status_code);
                }
            }
        }

        return $next($request);
    }
}
