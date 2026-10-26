<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Services\Technical\SeoCrawlActionPlanService;
use Quinrel\Laravel\Services\Technical\SiteCrawlOrchestrator;

class CrawlSiteCommand extends Command
{
    protected $signature = 'quinrel:crawl
                            {--limit=500 : Maximum number of seed URLs to crawl}
                            {--include-models : Include Eloquent models with Quinrel metadata}
                            {--chunk=10 : Concurrent HTTP pool chunk size}';

    protected $description = 'Crawl application URLs and generate a prioritized SEO remediation action plan';

    public function handle(
        SiteCrawlOrchestrator $orchestrator,
        SeoCrawlActionPlanService $actionPlanService
    ): int {
        $limit = max(1, (int) $this->option('limit'));
        $includeModels = (bool) $this->option('include-models');
        $chunk = max(1, (int) $this->option('chunk'));

        $this->info("Starting site crawl (limit: {$limit}, concurrency: {$chunk})...");

        $crawlResult = $orchestrator->crawl($limit, $includeModels, $chunk);
        $plan = $actionPlanService->buildActionPlan($crawlResult);

        Cache::put('quinrel.crawl.latest', [
            'crawled_at' => now()->toIso8601String(),
            'crawl' => $crawlResult,
            'plan' => $plan,
        ], now()->addDays(7));

        $this->info("Crawled {$plan['total_crawled']} URL(s). Overall Crawl Health Score: {$plan['score']}/100");

        $this->table(
            ['Priority', 'Severity', 'Tickets'],
            [
                ['P0', 'Critical', $plan['counts']['P0']],
                ['P1', 'High', $plan['counts']['P1']],
                ['P2', 'Medium', $plan['counts']['P2']],
                ['P3', 'Low', $plan['counts']['P3']],
            ]
        );

        if ($plan['tickets'] !== []) {
            $rows = array_map(
                fn (array $t): array => [
                    $t['priority'],
                    $t['category'],
                    $t['url'],
                    $t['title'],
                ],
                array_slice($plan['tickets'], 0, 20)
            );
            $this->table(['Priority', 'Category', 'URL', 'Issue'], $rows);
        }

        return self::SUCCESS;
    }
}
