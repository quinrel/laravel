<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Indexing;

use Holvek\Laravel\Models\HolvekIndexingLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class IndexNowClient
{
    private string $endpoint = 'https://api.indexnow.org/indexnow';

    /**
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): void
    {
        $key = config('holvek.api.indexnow_key');
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $key || ! $host) {
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
            HolvekIndexingLog::create([
                'url' => $url,
                'engine' => 'indexnow',
                'status_code' => $response->status(),
                'response' => $response->json() ?: [],
            ]);
        }
    }
}
