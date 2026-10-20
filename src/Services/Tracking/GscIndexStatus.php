<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Tracking;

class GscIndexStatus
{
    public function __construct(
        protected ?GscClient $client = null
    ) {
        $this->client ??= app(GscClient::class);
    }

    /**
     * Checks exact index status using the Google Search Console URL Inspection API.
     *
     * @return array<string, mixed>
     */
    public function check(string $url): array
    {
        return $this->client ? $this->client->inspectUrl($url) : [];
    }
}
