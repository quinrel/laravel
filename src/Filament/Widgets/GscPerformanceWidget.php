<?php

declare(strict_types=1);

namespace Holvek\Laravel\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Holvek\Laravel\Models\HolvekGscMetric;

class GscPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = 'Google Search Console - Site Performance (30 Days)';

    protected function getData(): array
    {
        $startDate = Carbon::now()->subDays(30)->format('Y-m-d');

        $metrics = HolvekGscMetric::whereNull('url')
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->get();

        $labels = $metrics->pluck('date')->map(fn ($date) => $date->format('M d'))->toArray();
        $clicks = $metrics->pluck('clicks')->toArray();
        $impressions = $metrics->pluck('impressions')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Clicks',
                    'data' => $clicks,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                [
                    'label' => 'Impressions',
                    'data' => $impressions,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
