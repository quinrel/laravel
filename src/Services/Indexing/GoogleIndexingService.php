<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Indexing;

use Holvek\Laravel\Models\HolvekIndexingLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleIndexingService
{
    private string $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    /**
     * Submit a URL to Google Indexing API.
     *
     * @param  string  $url  The URL to publish
     * @param  string  $type  Can be 'URL_UPDATED' or 'URL_DELETED'
     */
    public function submitUrl(string $url, string $type = 'URL_UPDATED'): bool
    {
        if (! config('holvek.google_indexing.enabled')) {
            return false;
        }

        $token = config('holvek.api.google_oauth_token');

        if (! $token) {
            Log::warning('Google Indexing API is enabled but no OAuth token is configured (HOLVEK_GOOGLE_OAUTH_TOKEN).');

            return false;
        }

        try {
            /** @var Response $response */
            $response = Http::withToken($token)
                ->timeout(10)
                ->post($this->endpoint, [
                    'url' => $url,
                    'type' => $type,
                ]);

            HolvekIndexingLog::create([
                'url' => $url,
                'engine' => 'google',
                'status_code' => $response->status(),
                'response' => $response->json() ?: [],
            ]);

            if ($response->failed()) {
                Log::warning("Google Indexing API submission failed for {$url}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Google Indexing API request exception for {$url}: ".$e->getMessage());

            HolvekIndexingLog::create([
                'url' => $url,
                'engine' => 'google',
                'status_code' => 500,
                'response' => ['error' => $e->getMessage()],
            ]);

            return false;
        }
    }
}
