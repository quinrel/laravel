<?php

declare(strict_types=1);

namespace Holvek\Laravel\Console\Commands;

use Carbon\Carbon;
use Holvek\Laravel\Models\HolvekGscMetric;
use Holvek\Laravel\Services\GoogleSearchConsoleService;
use Illuminate\Console\Command;
use Throwable;

class FetchGscDataCommand extends Command
{
    protected $signature = 'holvek:fetch-gsc-data {--date= : The date to fetch data for (YYYY-MM-DD)}';

    protected $description = 'Fetch Google Search Console metrics for the site and individual pages';

    public function handle(GoogleSearchConsoleService $gscService): int
    {
        // GSC data usually has a 2-3 day lag. Default to 3 days ago if not specified.
        $dateStr = $this->option('date');
        $date = is_string($dateStr) ? Carbon::parse($dateStr)->format('Y-m-d') : Carbon::now()->subDays(3)->format('Y-m-d');

        $siteUrl = config('holvek.gsc.site_url');
        if (! $siteUrl) {
            $this->error('GSC site_url is not configured in holvek.gsc.site_url.');

            return self::FAILURE;
        }

        $this->info("Fetching GSC data for {$siteUrl} on {$date}...");

        try {
            // 1. Site-wide metrics (no dimensions)
            $siteRows = $gscService->queryAnalytics($siteUrl, $date, []);
            if (! empty($siteRows)) {
                $siteData = $siteRows[0];
                HolvekGscMetric::updateOrCreate(
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

            // 2. URL-specific metrics (dimension: page)
            $pageRows = $gscService->queryAnalytics($siteUrl, $date, ['page']);
            $count = 0;
            foreach ($pageRows as $row) {
                if (! isset($row['keys'][0])) {
                    continue;
                }

                $url = $row['keys'][0];
                HolvekGscMetric::updateOrCreate(
                    ['url' => $url, 'date' => $date],
                    [
                        'clicks' => $row['clicks'] ?? 0,
                        'impressions' => $row['impressions'] ?? 0,
                        'ctr' => $row['ctr'] ?? 0,
                        'position' => $row['position'] ?? 0,
                    ]
                );
                $count++;
            }

            $this->info("Updated {$count} URL-specific metrics.");

        } catch (Throwable $e) {
            $this->error('Failed to fetch GSC data: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
