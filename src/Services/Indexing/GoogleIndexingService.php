<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Indexing;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Quinrel\Laravel\Models\QuinrelIndexingLog;
use Quinrel\Laravel\Services\GoogleSearchConsoleService;

class GoogleIndexingService
{
    private string $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    public function __construct(
        protected ?GoogleSearchConsoleService $gscService = null
    ) {
        $this->gscService ??= app(GoogleSearchConsoleService::class);
    }

    /**
     * Submit a URL to Google Indexing API.
     *
     * @param  string  $url  The URL to publish
     * @param  string  $type  Can be 'URL_UPDATED' or 'URL_DELETED'
     */
    public function submitUrl(string $url, string $type = 'URL_UPDATED'): bool
    {
        if (! config('quinrel.google_indexing.enabled')) {
            return false;
        }

        $token = $this->gscService?->resolveAccessToken('https://www.googleapis.com/auth/indexing');

        if (! $token) {
            Log::warning('Google Indexing API is enabled but no OAuth token or Service Account is configured.');

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

            QuinrelIndexingLog::create([
                'url' => $url,
                'engine' => 'google',
                'status_code' => $response->status(),
                'response' => is_array($response->json()) ? $response->json() : [],
            ]);

            if ($response->failed()) {
                Log::warning("Google Indexing API submission failed for {$url}", [
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Google Indexing API request exception for {$url}: ".$e->getMessage());

            QuinrelIndexingLog::create([
                'url' => $url,
                'engine' => 'google',
                'status_code' => 500,
                'response' => ['error' => $e->getMessage()],
            ]);

            return false;
        }
    }
}
