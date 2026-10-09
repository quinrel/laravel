<?php

declare(strict_types=1);

namespace Holvek\Laravel\Tests\Feature;

use Holvek\Laravel\Tests\TestCase;

class RumIntegrationTest extends TestCase
{
    public function test_it_ingests_rum_telemetry_correctly()
    {
        $payload = [
            'url' => 'https://example.com/blog',
            'metric_name' => 'LCP',
            'metric_value' => 2400.5,
            'rating' => 'good',
            'session_id' => 'abc-123',
        ];

        $response = $this->postJson(route('holvek.api.rum.store'), $payload);

        $response->assertStatus(202);

        $this->assertDatabaseHas('holvek_rum_metrics', [
            'url' => 'https://example.com/blog',
            'metric_name' => 'LCP',
            'metric_value' => 2400.5,
            'rating' => 'good',
            'session_id' => 'abc-123',
        ]);
    }
}
