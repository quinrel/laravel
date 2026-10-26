<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Quinrel\Laravel\Services\AuditLogService;
use Quinrel\Laravel\Services\Technical\SeoCrawlActionPlanService;
use Quinrel\Laravel\Services\Technical\SiteCrawlOrchestrator;
use Quinrel\Laravel\Services\Tracking\GscIndexStatus;

class DashboardController
{
    public function index(): View
    {
        Gate::authorize('view-quinrel');

        /** @var view-string $viewName */
        $viewName = 'quinrel::app';

        return view($viewName);
    }

    public function saveSetup(Request $request, AuditLogService $auditLog): JsonResponse
    {
        Gate::authorize('manage-quinrel');

        $validated = $request->validate([
            'siteName' => ['required', 'string', 'max:255'],
            'twitterHandle' => ['nullable', 'string', 'max:100'],
            'aiProvider' => ['required', 'string', 'in:ollama,openai,anthropic'],
        ]);

        Cache::forever('quinrel_setup_config', $validated);
        $auditLog->log('setup_wizard_completed', null, null, [], $validated);

        return response()->json([
            'status' => 'saved',
            'config' => $validated,
        ]);
    }

    public function diagnostics(): JsonResponse
    {
        Gate::authorize('view-quinrel');

        return response()->json([
            'crawl' => Cache::get('quinrel.crawl.latest'),
            'accessibility' => Cache::get('quinrel.a11y.latest'),
        ]);
    }

    public function inspectUrl(Request $request, GscIndexStatus $indexStatus): JsonResponse
    {
        Gate::authorize('view-quinrel');

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:500'],
        ]);

        $url = (string) $validated['url'];
        $limiterKey = 'quinrel:gsc-inspect:'.md5($url);

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            return response()->json([
                'message' => 'Rate limit exceeded for live URL inspection.',
                'retry_after' => RateLimiter::availableIn($limiterKey),
            ], 429);
        }

        RateLimiter::hit($limiterKey, 60);
        $result = $indexStatus->inspectAndPlan($url);

        return response()->json([
            'diagnosis' => $result['diagnosis']->toArray(),
            'actions' => $result['actions'],
        ]);
    }

    public function runCrawl(
        SiteCrawlOrchestrator $orchestrator,
        SeoCrawlActionPlanService $planService
    ): JsonResponse {
        Gate::authorize('manage-quinrel');

        $limit = (int) config('quinrel.crawl.max_urls', 500);
        $concurrency = (int) config('quinrel.crawl.concurrency', 10);

        $crawl = $orchestrator->crawl($limit, true, $concurrency);
        $plan = $planService->buildActionPlan($crawl);

        $payload = [
            'crawled_at' => now()->toIso8601String(),
            'crawl' => $crawl,
            'plan' => $plan,
        ];

        Cache::put('quinrel.crawl.latest', $payload, now()->addDays(7));

        return response()->json($payload);
    }
}
