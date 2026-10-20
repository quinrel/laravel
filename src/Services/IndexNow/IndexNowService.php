<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\IndexNow;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Quinrel\Laravel\Models\QuinrelIndexingLog;

class IndexNowService
{
    /**
     * Submit URLs to configured IndexNow search engines and record indexing logs.
     *
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): bool
    {
        $key = (string) (config('quinrel.indexnow.key') ?: config('quinrel.api.indexnow_key', ''));

        if (! config('quinrel.indexnow.enabled') || $key === '') {
            return false;
        }

        if ($urls === []) {
            return true;
        }

        /** @var array<int, string> $engines */
        $engines = config('quinrel.indexnow.search_engines', ['api.indexnow.org']);
        $host = parse_url($urls[0], PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            $host = request()->getHost();
        }

        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => "https://{$host}/{$key}.txt",
            'urlList' => $urls,
        ];

        $success = true;
        $logged = false;

        foreach ($engines as $engine) {
            $response = Http::timeout(5)->post("https://{$engine}/indexnow", $payload);

            if (! $logged) {
                $this->logSubmissions($urls, $response->status(), $response->json());
                $logged = true;
            }

            if ($response->failed()) {
                Log::warning("IndexNow submission failed for {$engine}", [
                    'status' => $response->status(),
                ]);
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @param  array<int, string>  $urls
     */
    protected function logSubmissions(array $urls, int $statusCode, mixed $jsonResponse): void
    {
        try {
            $payload = is_array($jsonResponse) ? $jsonResponse : [];
            foreach ($urls as $url) {
                QuinrelIndexingLog::create([
                    'url' => $url,
                    'engine' => 'indexnow',
                    'status_code' => $statusCode,
                    'response' => $payload,
                ]);
            }
        } catch (\Throwable) {
            // Do not fail IndexNow ping if indexing log table is unavailable
        }
    }
}
