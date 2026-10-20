<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Quinrel\Laravel\Models\QuinrelGscMetric;
use Quinrel\Laravel\Services\GoogleSearchConsoleService;
use Throwable;

class FetchGscDataCommand extends Command
{
    protected $signature = 'quinrel:fetch-gsc-data
                            {--date= : The date to fetch data for (YYYY-MM-DD)}
                            {--force : Fail if GSC credentials are not configured}';

    protected $description = 'Fetch Google Search Console metrics for the site and individual pages';

    public function handle(GoogleSearchConsoleService $gscService): int
    {
        $hasCredentials = (bool) (config('quinrel.api.gsc_service_account') || config('quinrel.api.google_oauth_token'));
        if (! $hasCredentials && ! $this->option('force')) {
            $this->info('Google Search Console credentials are not configured; skipping fetch.');

            return self::SUCCESS;
        }

        $dateStr = $this->option('date');
        $date = is_string($dateStr) ? Carbon::parse($dateStr)->format('Y-m-d') : Carbon::now()->subDays(3)->format('Y-m-d');

        $siteUrl = config('quinrel.gsc.site_url');
        if (! is_string($siteUrl) || $siteUrl === '') {
            $this->error('GSC site_url is not configured in quinrel.gsc.site_url.');

            return self::FAILURE;
        }

        $this->info("Fetching GSC data for {$siteUrl} on {$date}...");

        try {
            // 1. Site-wide metrics (no dimensions)
            $siteRows = $gscService->queryAnalytics($siteUrl, $date, []);
            if ($siteRows !== []) {
                $siteData = $siteRows[0];
                QuinrelGscMetric::updateOrCreate(
                    ['url' => null, 'date' => $date],
                    [
                        'clicks' => $siteData['clicks'] ?? 0,
                        'impressions' => $siteData['impressions'] ?? 0,
                        'ctr' => $siteData['ctr'] ?? 0,
                        'position' => $siteData['position'] ?? 0,
                    ]
                );
                $this->info("Site-wide metrics updated: {$siteData['clicks']} clicks, {$siteData['impressions']} impressions.");
            } else {
                $this->warn("No site-wide data found for {$date}.");
            }

            // 2. URL-specific metrics (dimension: page) — batched upsert for O(1) query overhead
            $pageRows = $gscService->queryAnalytics($siteUrl, $date, ['page']);
            $now = now();
            $dateForDb = Carbon::parse($date)->startOfDay()->toDateTimeString();
            $batch = [];

            foreach ($pageRows as $row) {
                if (! isset($row['keys'][0])) {
                    continue;
                }

                $batch[] = [
                    'url' => (string) $row['keys'][0],
                    'date' => $dateForDb,
                    'clicks' => (int) ($row['clicks'] ?? 0),
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'ctr' => (float) ($row['ctr'] ?? 0),
                    'position' => (float) ($row['position'] ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($batch, 500) as $chunk) {
                QuinrelGscMetric::upsert(
                    $chunk,
                    ['url', 'date'],
                    ['clicks', 'impressions', 'ctr', 'position', 'updated_at']
                );
            }

            $count = count($batch);
            $this->info("Updated {$count} URL-specific metrics.");

        } catch (Throwable $e) {
            $this->error('Failed to fetch GSC data: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
