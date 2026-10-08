<?php

declare(strict_types=1);

namespace Holvek\Laravel\Facades;

use Holvek\Laravel\Middleware\InertiaSeoMiddleware;
use Holvek\Laravel\Middleware\LivewireSeoMiddleware;
use Illuminate\Support\Facades\Facade;

class Holvek extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'holvek';
    }

    public static function inertia(): string
    {
        return InertiaSeoMiddleware::class;
    }

    public static function livewire(): string
    {
        return LivewireSeoMiddleware::class;
    }
}
