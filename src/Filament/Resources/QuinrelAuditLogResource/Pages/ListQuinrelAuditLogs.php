<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources\QuinrelAuditLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Quinrel\Laravel\Filament\Resources\QuinrelAuditLogResource;

class ListQuinrelAuditLogs extends ListRecords
{
    protected static string $resource = QuinrelAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
