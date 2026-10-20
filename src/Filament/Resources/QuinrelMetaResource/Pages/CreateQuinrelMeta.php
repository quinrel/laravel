<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources\QuinrelMetaResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Quinrel\Laravel\Filament\Resources\QuinrelMetaResource;

class CreateQuinrelMeta extends CreateRecord
{
    protected static string $resource = QuinrelMetaResource::class;
}
