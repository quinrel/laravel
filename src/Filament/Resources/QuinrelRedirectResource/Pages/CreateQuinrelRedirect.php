<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Resources\QuinrelRedirectResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Quinrel\Laravel\Filament\Resources\QuinrelRedirectResource;

class CreateQuinrelRedirect extends CreateRecord
{
    protected static string $resource = QuinrelRedirectResource::class;
}
