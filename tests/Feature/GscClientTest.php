<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Services\Tracking\GoogleAlgorithmUpdates;
use Holvek\Laravel\Services\Tracking\GscClient;
use Holvek\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class GscClientTest extends TestCase
{
    public function test_it_can_submit_url_to_indexing_api(): void
    {
        config(['holvek.api.google_oauth_token' => 'fake-token']);

        Http::fake([
            'indexing.googleapis.com/*' => Http::response(['urlNotificationMetadata' => ['latestUpdate' => ['type' => 'URL_UPDATED']]], 200),
        ]);

        $client = new GscClient(new GoogleAlgorithmUpdates);
        $result = $client->submitUrl('https://example.com');

        $this->assertArrayHasKey('urlNotificationMetadata', $result);
    }
}
