<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Pages;

use Filament\Pages\Page;
use Quinrel\Laravel\Models\QuinrelIndexingLog;

class IndexingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'quinrel::filament.pages.indexing-dashboard';

    protected static ?string $navigationGroup = 'Quinrel SEO';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $since = now()->subDay();

        $indexNowSent = QuinrelIndexingLog::where('engine', 'indexnow')
            ->where('created_at', '>=', $since)
            ->count();

        $googleSent = QuinrelIndexingLog::where('engine', 'google')
            ->where('created_at', '>=', $since)
            ->count();

        $recentLogs = QuinrelIndexingLog::query()
            ->latest()
            ->limit(15)
            ->get();

        return [
            'indexNowSent' => $indexNowSent,
            'indexNowQuota' => 10000,
            'googleSent' => $googleSent,
            'googleQuota' => 200,
            'recentLogs' => $recentLogs,
        ];
    }
}
