<?php

declare(strict_types=1);

namespace Holvek\Laravel\Filament\Resources\HolvekAuditLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Holvek\Laravel\Filament\Resources\HolvekAuditLogResource;

class ListHolvekAuditLogs extends ListRecords
{
    protected static string $resource = HolvekAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
