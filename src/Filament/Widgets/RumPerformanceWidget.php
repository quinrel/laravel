<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Models\QuinrelRumMetric;

class RumPerformanceWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $lcp = $this->getPercentile('LCP', 75);
        $inp = $this->getPercentile('INP', 75);
        $cls = $this->getPercentile('CLS', 75);

        return [
            Stat::make('LCP (75th Percentile)', $lcp !== null ? number_format($lcp, 2).' ms' : 'N/A')
                ->description('Largest Contentful Paint')
                ->descriptionIcon('heroicon-m-clock')
                ->color($lcp === null ? 'gray' : ($lcp < 2500 ? 'success' : ($lcp < 4000 ? 'warning' : 'danger'))),

            Stat::make('INP (75th Percentile)', $inp !== null ? number_format($inp, 2).' ms' : 'N/A')
                ->description('Interaction to Next Paint')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color($inp === null ? 'gray' : ($inp < 200 ? 'success' : ($inp < 500 ? 'warning' : 'danger'))),

            Stat::make('CLS (75th Percentile)', $cls !== null ? number_format($cls, 4) : 'N/A')
                ->description('Cumulative Layout Shift')
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color($cls === null ? 'gray' : ($cls < 0.1 ? 'success' : ($cls < 0.25 ? 'warning' : 'danger'))),
        ];
    }

    protected function getPercentile(string $metricName, int $percentile): ?float
    {
        $cacheKey = "quinrel:rum:p{$percentile}:{$metricName}";

        return Cache::remember($cacheKey, 60, function () use ($metricName, $percentile): ?float {
            $baseQuery = QuinrelRumMetric::query()
                ->where('metric_name', $metricName)
                ->where('created_at', '>=', now()->subDays(30));

            $count = (clone $baseQuery)->count();
            if ($count === 0) {
                return null;
            }

            $index = max(0, (int) ceil(($percentile / 100) * $count) - 1);

            $value = $baseQuery
                ->orderBy('metric_value')
                ->skip($index)
                ->value('metric_value');

            return $value !== null ? (float) $value : null;
        });
    }
}
