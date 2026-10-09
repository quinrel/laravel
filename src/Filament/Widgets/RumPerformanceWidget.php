<?php

declare(strict_types=1);

namespace Holvek\Laravel\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Holvek\Laravel\Models\HolvekRumMetric;

class RumPerformanceWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Calculate 75th percentile approximately for LCP, INP, CLS
        $lcp = $this->getPercentile('LCP', 75);
        $inp = $this->getPercentile('INP', 75);
        $cls = $this->getPercentile('CLS', 75);

        return [
            Stat::make('LCP (75th Percentile)', $lcp ? number_format($lcp, 2).' ms' : 'N/A')
                ->description('Largest Contentful Paint')
                ->descriptionIcon('heroicon-m-clock')
                ->color($lcp < 2500 ? 'success' : ($lcp < 4000 ? 'warning' : 'danger')),

            Stat::make('INP (75th Percentile)', $inp ? number_format($inp, 2).' ms' : 'N/A')
                ->description('Interaction to Next Paint')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color($inp < 200 ? 'success' : ($inp < 500 ? 'warning' : 'danger')),

            Stat::make('CLS (75th Percentile)', $cls ? number_format($cls, 4) : 'N/A')
                ->description('Cumulative Layout Shift')
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color($cls < 0.1 ? 'success' : ($cls < 0.25 ? 'warning' : 'danger')),
        ];
    }

    protected function getPercentile(string $metricName, int $percentile): ?float
    {
        // Simple approximate percentile calculation for SQLite/MySQL compatibility without raw queries.
        $values = HolvekRumMetric::where('metric_name', $metricName)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('metric_value')
            ->pluck('metric_value');

        $count = $values->count();
        if ($count === 0) {
            return null;
        }

        $index = (int) ceil(($percentile / 100) * $count) - 1;

        return (float) $values[$index];
    }
}
