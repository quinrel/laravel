<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources\QuinrelRedirectResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Quinrel\Laravel\Filament\Resources\QuinrelRedirectResource;

class EditQuinrelRedirect extends EditRecord
{
    protected static string $resource = QuinrelRedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
