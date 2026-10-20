<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources\QuinrelMetaResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Quinrel\Laravel\Filament\Resources\QuinrelMetaResource;

class ListQuinrelMetas extends ListRecords
{
    protected static string $resource = QuinrelMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
