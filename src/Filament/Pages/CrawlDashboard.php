<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Services\Technical\Remediation\AutoRedirectCreator;
use Quinrel\Laravel\Services\Technical\SeoCrawlActionPlanService;
use Quinrel\Laravel\Services\Technical\SiteCrawlOrchestrator;

class CrawlDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static string $view = 'quinrel::filament.pages.crawl-dashboard';

    protected static ?string $navigationGroup = 'Quinrel SEO';

    protected static ?string $navigationLabel = 'Crawl Health';

    protected static ?string $title = 'SEO Crawl Health & Remediation';

    public function runCrawlNow(): void
    {
        $orchestrator = app(SiteCrawlOrchestrator::class);
        $planService = app(SeoCrawlActionPlanService::class);

        $limit = (int) config('quinrel.crawl.max_urls', 500);
        $concurrency = (int) config('quinrel.crawl.concurrency', 10);

        $crawl = $orchestrator->crawl($limit, true, $concurrency);
        $plan = $planService->buildActionPlan($crawl);

        Cache::put('quinrel.crawl.latest', [
            'crawled_at' => now()->toIso8601String(),
            'crawl' => $crawl,
            'plan' => $plan,
        ], now()->addDays(7));
    }

    public function createSuggestedRedirect(string $brokenUrl): void
    {
        app(AutoRedirectCreator::class)->createFrom404($brokenUrl);
        $this->runCrawlNow();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $cached = Cache::get('quinrel.crawl.latest');
        $plan = is_array($cached['plan'] ?? null) ? $cached['plan'] : [
            'score' => 100,
            'total_crawled' => 0,
            'counts' => ['P0' => 0, 'P1' => 0, 'P2' => 0, 'P3' => 0],
            'tickets' => [],
        ];

        return [
            'crawledAt' => is_string($cached['crawled_at'] ?? null) ? $cached['crawled_at'] : null,
            'score' => (int) ($plan['score'] ?? 100),
            'totalCrawled' => (int) ($plan['total_crawled'] ?? 0),
            'counts' => $plan['counts'] ?? ['P0' => 0, 'P1' => 0, 'P2' => 0, 'P3' => 0],
            'tickets' => $plan['tickets'] ?? [],
        ];
    }
}
