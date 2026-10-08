<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Services\Technical\BrokenLinkChecker;
use Holvek\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class BrokenLinkCheckerTest extends TestCase
{
    public function test_it_correctly_identifies_broken_links(): void
    {
        Http::fake([
            'https://example.com/good' => Http::response('', 200),
            'https://example.com/not-found' => Http::response('', 404),
            'https://example.com/server-error' => Http::response('', 500),
        ]);

        $checker = new BrokenLinkChecker;
        $urls = [
            'https://example.com/good',
            'https://example.com/not-found',
            'https://example.com/server-error',
        ];

        $results = $checker->check($urls);

        $this->assertCount(3, $results);

        $this->assertEquals(200, $results['https://example.com/good']['status']);
        $this->assertFalse($results['https://example.com/good']['is_broken']);

        $this->assertEquals(404, $results['https://example.com/not-found']['status']);
        $this->assertTrue($results['https://example.com/not-found']['is_broken']);

        $this->assertEquals(500, $results['https://example.com/server-error']['status']);
        $this->assertTrue($results['https://example.com/server-error']['is_broken']);
    }
}
