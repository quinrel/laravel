<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Indexing;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Models\QuinrelIndexingLog;

class IndexNowClient
{
    private string $endpoint = 'https://api.indexnow.org/indexnow';

    /**
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): void
    {
        $key = (string) (config('quinrel.api.indexnow_key') ?: config('quinrel.indexnow.key', ''));
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($key === '' || ! is_string($host) || $host === '' || $urls === []) {
            return;
        }

        /** @var Response $response */
        $response = Http::timeout(10)->post($this->endpoint, [
            'host' => $host,
            'key' => $key,
            'keyLocation' => "https://{$host}/{$key}.txt",
            'urlList' => $urls,
        ]);

        foreach ($urls as $url) {
            QuinrelIndexingLog::create([
                'url' => $url,
                'engine' => 'indexnow',
                'status_code' => $response->status(),
                'response' => is_array($response->json()) ? $response->json() : [],
            ]);
        }
    }
}
