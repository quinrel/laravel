<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Models\QuinrelRedirect;
use Symfony\Component\HttpFoundation\Response;

class QuinrelRedirectMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();
        $normalizedPath = '/'.ltrim($path, '/');
        $fullUrl = $request->fullUrl();

        /**
         * @var array{
         *     exact: array<string, array{id: int, target_url: string, status_code: int, is_loop: bool, is_chain: bool, chain_details: array<int, string>|null}>,
         *     regex: array<int, array{id: int, source_url: string, target_url: string, status_code: int, is_loop: bool}>
         * } $map
         */
        $map = Cache::remember('quinrel_redirects_map', 3600, function (): array {
            $exact = [];
            $regex = [];

            $redirects = QuinrelRedirect::where('is_active', true)->get();

            foreach ($redirects as $redirect) {
                if ($redirect->is_regex) {
                    $regex[] = [
                        'id' => $redirect->id,
                        'source_url' => $redirect->source_url,
                        'target_url' => $redirect->target_url,
                        'status_code' => $redirect->status_code,
                        'is_loop' => $redirect->is_loop,
                    ];
                } else {
                    $exact[$redirect->source_url] = [
                        'id' => $redirect->id,
                        'target_url' => $redirect->target_url,
                        'status_code' => $redirect->status_code,
                        'is_loop' => $redirect->is_loop,
                        'is_chain' => $redirect->is_chain,
                        'chain_details' => $redirect->chain_details,
                    ];
                }
            }

            return ['exact' => $exact, 'regex' => $regex];
        });

        // 1. Check exact match in O(1)
        $matched = $map['exact'][$path]
            ?? $map['exact'][$normalizedPath]
            ?? $map['exact'][$fullUrl]
            ?? null;

        if ($matched !== null) {
            if ($matched['is_loop']) {
                abort(508, 'Loop Detected');
            }

            $targetUrl = $matched['target_url'];

            if ($matched['is_chain'] && ! empty($matched['chain_details'])) {
                $chain = $matched['chain_details'];
                $last = end($chain);
                if ($last !== '') {
                    $targetUrl = $last;
                }
            }

            QuinrelRedirect::whereKey($matched['id'])->increment('hits');

            return redirect()->to($targetUrl, $matched['status_code']);
        }

        // 2. Check active regex matches safely
        foreach ($map['regex'] as $regexRedirect) {
            if ($regexRedirect['is_loop']) {
                continue;
            }

            $pattern = '#^'.str_replace('#', '\#', $regexRedirect['source_url']).'$#';

            if (@preg_match($pattern, $path) === 1) {
                $target = @preg_replace($pattern, $regexRedirect['target_url'], $path);
                if (is_string($target)) {
                    QuinrelRedirect::whereKey($regexRedirect['id'])->increment('hits');

                    return redirect()->to($target, $regexRedirect['status_code']);
                }
            } elseif (@preg_match($pattern, $normalizedPath) === 1) {
                $target = @preg_replace($pattern, $regexRedirect['target_url'], $normalizedPath);
                if (is_string($target)) {
                    QuinrelRedirect::whereKey($regexRedirect['id'])->increment('hits');

                    return redirect()->to($target, $regexRedirect['status_code']);
                }
            }
        }

        return $next($request);
    }
}
