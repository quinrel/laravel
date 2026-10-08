<?php

declare(strict_types=1);

namespace Holvek\Laravel\Filament\Pages;

use Filament\Pages\Page;

class IndexingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'holvek::filament.pages.indexing-dashboard';

    protected static ?string $navigationGroup = 'Holvek SEO';
}
