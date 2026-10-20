<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Quinrel\Laravel\Jobs\PingIndexNowJob;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\IndexNow\IndexNowService;
use Quinrel\Laravel\Tests\TestCase;

class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['quinrel.indexnow.enabled' => true]);
        config(['quinrel.indexnow.key' => '1234567890abcdef1234567890abcdef']);
    }

    public function test_it_dispatches_job_when_meta_saved(): void
    {
        Queue::fake();

        QuinrelMeta::forceCreate([
            'model_type' => User::class,
            'model_id' => 1,
            'canonical_url' => 'https://example.com/test-url',
        ]);

        Queue::assertPushed(PingIndexNowJob::class, function ($job) {
            return $job->urls === ['https://example.com/test-url'];
        });
    }

    public function test_it_pings_engines(): void
    {
        Http::fake([
            'api.indexnow.org/indexnow' => Http::response('OK', 200),
            'www.bing.com/indexnow' => Http::response('OK', 200),
            'yandex.com/indexnow' => Http::response('OK', 200),
        ]);

        $service = new IndexNowService;
        $success = $service->submit(['https://example.com/test']);

        $this->assertTrue($success);
        Http::assertSentCount(3);
    }
}
