<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class QuinrelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'quinrel';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                Resources\QuinrelMetaResource::class,
                Resources\QuinrelRedirectResource::class,
                Resources\Quinrel404LogResource::class,
                Resources\QuinrelAuditLogResource::class,
            ])
            ->pages([
                Pages\IndexingDashboard::class,
            ])
            ->widgets([
                Widgets\GscPerformanceWidget::class,
                Widgets\RumPerformanceWidget::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
