<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Quinrel\Laravel\Services\HeadRenderer;
use Symfony\Component\HttpFoundation\Response;

class LivewireSeoMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $seo = app(HeadRenderer::class)->resolveMetadata();
        View::share('quinrel_seo', $seo);

        if ($request->hasHeader('X-Livewire')) {
            $response->headers->set('X-Quinrel-Title', rawurlencode($seo['title']));
        }

        return $response;
    }
}
