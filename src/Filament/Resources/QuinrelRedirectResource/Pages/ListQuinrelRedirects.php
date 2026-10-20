<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources\QuinrelRedirectResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Quinrel\Laravel\Filament\Resources\QuinrelRedirectResource;

class ListQuinrelRedirects extends ListRecords
{
    protected static string $resource = QuinrelRedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
