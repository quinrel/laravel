<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Quinrel\Laravel\Middleware\AeoContentNegotiationMiddleware;
use Quinrel\Laravel\Middleware\InertiaSeoMiddleware;
use Quinrel\Laravel\Middleware\LivewireSeoMiddleware;
use Quinrel\Laravel\Services\HeadRenderer;

/**
 * @method static HeadRenderer setTitle(string $title)
 * @method static HeadRenderer setDescription(string $description)
 * @method static HeadRenderer setCanonical(string $canonical)
 * @method static HeadRenderer setRobots(string $robots)
 * @method static HeadRenderer setOgImage(string $ogImage)
 * @method static HeadRenderer setHreflangs(array<string, string> $hreflangs)
 * @method static HeadRenderer setSchema(array<string, mixed> $schema)
 * @method static HeadRenderer addPreload(string $url, string $as = 'image', ?string $type = null)
 * @method static string renderPreload(string $url, string $as = 'image', ?string $type = null)
 * @method static string optimizeContentImages(string $html)
 * @method static HeadRenderer resetOverrides()
 * @method static string render(?object $model = null)
 * @method static array<string, mixed> resolveMetadata(?object $model = null, ?bool $isProduction = null)
 *
 * @see HeadRenderer
 */
class Quinrel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'quinrel';
    }

    public static function inertia(): string
    {
        return InertiaSeoMiddleware::class;
    }

    public static function livewire(): string
    {
        return LivewireSeoMiddleware::class;
    }

    public static function aeo(): string
    {
        return AeoContentNegotiationMiddleware::class;
    }
}
