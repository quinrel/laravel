<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Tracking\KeywordRankTracker;
use Quinrel\Laravel\Services\Tracking\SerpApiClient;
use Quinrel\Laravel\Tests\TestCase;

class KeywordRankTrackerTest extends TestCase
{
    public function test_it_tracks_keywords_and_logs_rankings(): void
    {
        config(['quinrel.api.serpapi_key' => 'fake-key']);

        QuinrelMeta::forceCreate([
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

        $this->assertDatabaseHas('quinrel_keyword_rankings', [
            'keyword' => 'test keyword',
            'url' => 'https://example.com/test',
            'rank' => 2,
        ]);

        $charts = $tracker->getHistoryCharts('test keyword');
        $this->assertArrayHasKey('7_days', $charts);
        $this->assertCount(1, $charts['7_days']);
    }
}
