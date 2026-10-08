<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Models\HolvekMeta;
use Holvek\Laravel\Services\Tracking\KeywordRankTracker;
use Holvek\Laravel\Services\Tracking\SerpApiClient;
use Holvek\Laravel\Tests\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Http;

class KeywordRankTrackerTest extends TestCase
{
    public function test_it_tracks_keywords_and_logs_rankings(): void
    {
        config(['holvek.api.serpapi_key' => 'fake-key']);

        HolvekMeta::forceCreate([
            'model_type' => User::class,
            'model_id' => 1,
            'target_keyword' => 'test keyword',
            'canonical_url' => 'https://example.com/test',
        ]);

        Http::fake([
            'serpapi.com/*' => Http::response([
                'organic_results' => [
                    ['position' => 1, 'link' => 'https://other.com'],
                    ['position' => 2, 'link' => 'https://example.com/test'],
                ],
            ], 200),
        ]);

        $tracker = new KeywordRankTracker(new SerpApiClient);
        $tracker->trackAllKeywords();

        $this->assertDatabaseHas('holvek_keyword_rankings', [
            'keyword' => 'test keyword',
            'url' => 'https://example.com/test',
            'rank' => 2,
        ]);

        $charts = $tracker->getHistoryCharts('test keyword');
        $this->assertArrayHasKey('7_days', $charts);
        $this->assertCount(1, $charts['7_days']);
    }
}
