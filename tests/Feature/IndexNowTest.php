<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Jobs\PingIndexNowJob;
use Holvek\Laravel\Models\HolvekMeta;
use Holvek\Laravel\Services\IndexNow\IndexNowService;
use Holvek\Laravel\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['holvek.indexnow.enabled' => true]);
        config(['holvek.indexnow.key' => '1234567890abcdef1234567890abcdef']);
    }

    public function test_it_dispatches_job_when_meta_saved(): void
    {
        Queue::fake();

        HolvekMeta::forceCreate([
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
