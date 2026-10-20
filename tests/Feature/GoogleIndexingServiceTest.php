<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Services\Indexing\GoogleIndexingService;
use Quinrel\Laravel\Tests\TestCase;

class GoogleIndexingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_submit_url_to_indexing_api(): void
    {
        config(['quinrel.api.google_oauth_token' => 'fake-token']);
        config(['quinrel.google_indexing.enabled' => true]);

        Http::fake([
            'indexing.googleapis.com/*' => Http::response(['urlNotificationMetadata' => ['latestUpdate' => ['type' => 'URL_UPDATED']]], 200),
        ]);

        $service = new GoogleIndexingService;
        $result = $service->submitUrl('https://example.com');

        $this->assertTrue($result);
        $this->assertDatabaseHas('quinrel_indexing_logs', [
            'url' => 'https://example.com',
            'engine' => 'google',
            'status_code' => 200,
        ]);
    }
}
