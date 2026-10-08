<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Cloud;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HolvekCloudClient
{
    private string $cloudApiUrl = 'https://api.holvek.com/v1';

    public function requestHeavyAudit(string $url): bool
    {
        $apiKey = config('holvek.api.cloud_key');
        if (! $apiKey) {
            return false;
        }

        /** @var Response $response */
        $response = Http::withToken((string) $apiKey)
            ->timeout(10)
            ->post("{$this->cloudApiUrl}/audit", [
                'url' => $url,
                'webhook_url' => route('holvek.webhooks.audit'),
            ]);

        return $response->successful();
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('holvek.api.webhook_secret');
        if (! $secret) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, (string) $secret);

        return hash_equals($expected, $signature);
    }
}
