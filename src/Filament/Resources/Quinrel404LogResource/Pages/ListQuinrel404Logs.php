<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources\Quinrel404LogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Quinrel\Laravel\Filament\Resources\Quinrel404LogResource;

class ListQuinrel404Logs extends ListRecords
{
    protected static string $resource = Quinrel404LogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
