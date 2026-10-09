<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class GscIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('holvek.gsc.site_url', 'https://example.com');
        Config::set('holvek.api.google_oauth_token', 'test-token');
    }

    public function test_it_fetches_gsc_data_and_stores_metrics()
    {
        Http::fake([
            'searchconsole.googleapis.com/*' => Http::response([
                'rows' => [
                    [
                        'keys' => ['https://example.com/about'],
                        'clicks' => 150,
                        'impressions' => 1500,
                        'ctr' => 0.1,
                        'position' => 3.5,
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('holvek:fetch-gsc-data', ['--date' => '2026-01-01'])
            ->assertExitCode(0);

        // It hits the API twice: once without dimensions (returns the faked payload which isn't perfect for site-wide but will just have null url in this mock logic due to how we check keys)
        // Actually, our command checks if keys exist for URL specific, and just takes [0] for site-wide.
        // Let's assert database has the records.

        $this->assertDatabaseHas('holvek_gsc_metrics', [
            'date' => '2026-01-01 00:00:00',
            'clicks' => 150,
            'url' => null,
        ]);

        $this->assertDatabaseHas('holvek_gsc_metrics', [
            'date' => '2026-01-01 00:00:00',
            'clicks' => 150,
            'url' => 'https://example.com/about',
        ]);
    }
}
