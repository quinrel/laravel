<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Unit;

use Quinrel\Laravel\QuinrelServiceProvider;
use Quinrel\Laravel\Tests\TestCase;

class ProviderTest extends TestCase
{
    public function test_it_registers_provider(): void
    {
        $this->assertArrayHasKey(QuinrelServiceProvider::class, $this->app->getLoadedProviders());
    }
}
