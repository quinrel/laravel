<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources\QuinrelMetaResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Quinrel\Laravel\Filament\Resources\QuinrelMetaResource;

class EditQuinrelMeta extends EditRecord
{
    protected static string $resource = QuinrelMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
