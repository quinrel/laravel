<?php

declare(strict_types=1);

namespace Holvek\Laravel\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class HolvekPlugin implements Plugin
{
    public function getId(): string
    {
        return 'holvek';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                Resources\HolvekMetaResource::class,
                Resources\HolvekRedirectResource::class,
                Resources\Holvek404LogResource::class,
                Resources\HolvekAuditLogResource::class,
            ])
            ->pages([
                Pages\IndexingDashboard::class,
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
