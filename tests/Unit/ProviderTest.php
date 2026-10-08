<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Unit;

use Holvek\Laravel\HolvekServiceProvider;
use Holvek\Laravel\Tests\TestCase;

class ProviderTest extends TestCase
{
    public function test_it_registers_provider(): void
    {
        $this->assertArrayHasKey(HolvekServiceProvider::class, $this->app->getLoadedProviders());
    }
}
