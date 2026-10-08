<?php

declare(strict_types=1);

namespace Holvek\Laravel\Filament\Resources\Holvek404LogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Holvek\Laravel\Filament\Resources\Holvek404LogResource;

class ListHolvek404Logs extends ListRecords
{
    protected static string $resource = Holvek404LogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
