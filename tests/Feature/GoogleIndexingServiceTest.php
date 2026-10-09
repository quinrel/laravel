<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Services\Indexing\GoogleIndexingService;
use Holvek\Laravel\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class GoogleIndexingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_submit_url_to_indexing_api(): void
    {
        config(['holvek.api.google_oauth_token' => 'fake-token']);
        config(['holvek.google_indexing.enabled' => true]);

        Http::fake([
            'indexing.googleapis.com/*' => Http::response(['urlNotificationMetadata' => ['latestUpdate' => ['type' => 'URL_UPDATED']]], 200),
        ]);

        $service = new GoogleIndexingService;
        $result = $service->submitUrl('https://example.com');

        $this->assertTrue($result);
        $this->assertDatabaseHas('holvek_indexing_logs', [
            'url' => 'https://example.com',
            'engine' => 'google',
            'status_code' => 200,
        ]);
    }
}
