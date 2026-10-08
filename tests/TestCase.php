<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests;

use Holvek\Laravel\HolvekServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            HolvekServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $migration = include __DIR__.'/../database/migrations/2026_01_01_000001_create_holvek_meta_table.php';
        $migration->up();

        $migration2 = include __DIR__.'/../database/migrations/2026_01_01_000004_add_target_keyword_to_holvek_meta_table.php';
        $migration2->up();

        $migration3 = include __DIR__.'/../database/migrations/2026_01_01_000005_create_holvek_keyword_rankings_table.php';
        $migration3->up();
    }
}
