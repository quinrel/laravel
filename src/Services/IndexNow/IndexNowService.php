<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\IndexNow;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexNowService
{
    /**
     * Submit URLs to configured IndexNow search engines.
     *
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): bool
    {
        if (! config('holvek.indexnow.enabled') || ! config('holvek.indexnow.key')) {
            return false;
        }

        if (empty($urls)) {
            return true;
        }

        $engines = config('holvek.indexnow.search_engines', []);
        $key = config('holvek.indexnow.key');
        $host = parse_url($urls[0], PHP_URL_HOST);

        if (! $host) {
            $host = request()->getHost();
        }

        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => "https://{$host}/{$key}.txt",
            'urlList' => $urls,
        ];

        $success = true;

        foreach ($engines as $engine) {
            $response = Http::timeout(5)->post("https://{$engine}/indexnow", $payload);

            if ($response->failed()) {
                Log::warning("IndexNow submission failed for {$engine}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $success = false;
            }
        }

        return $success;
    }
}
