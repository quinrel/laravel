<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Indexing;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Quinrel\Laravel\Jobs\PingGoogleIndexingJob;
use Quinrel\Laravel\Jobs\PingIndexNowJob;
use Quinrel\Laravel\Models\QuinrelIndexingLog;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\GoogleSearchConsoleService;
use Quinrel\Laravel\Services\Tracking\GscIndexStatus;

class GoogleIndexingService
{
    private string $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    public function __construct(
        protected ?GoogleSearchConsoleService $gscService = null,
        protected ?GscIndexStatus $indexStatus = null,
    ) {
        $this->gscService ??= app(GoogleSearchConsoleService::class);
        $this->indexStatus ??= app(GscIndexStatus::class);
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

    /**
     * 1-Click Autofix: Adopt Google's selected canonical URL on a QuinrelMeta record and queue re-indexing.
     */
    public function adoptGoogleCanonical(QuinrelMeta $meta, ?string $googleCanonical = null): bool
    {
        if ($googleCanonical === null || trim($googleCanonical) === '') {
            if (! $meta->canonical_url) {
                return false;
            }

            $diagnosis = $this->indexStatus?->diagnose($meta->canonical_url);
            $googleCanonical = $diagnosis?->googleCanonical;
        }

        if ($googleCanonical === null || trim($googleCanonical) === '') {
            return false;
        }

        $googleCanonical = trim($googleCanonical);
        $robots = (string) ($meta->robots ?? 'index, follow');
        if (str_contains(strtolower($robots), 'noindex')) {
            $robots = 'index, follow';
        }

        QuinrelMeta::withoutEvents(function () use ($meta, $googleCanonical, $robots): void {
            $meta->update([
                'canonical_url' => $googleCanonical,
                'robots' => $robots,
            ]);
        });

        $this->dispatchResubmissionJobs($googleCanonical);

        return true;
    }

    /**
     * Resolve an indexing exclusion (e.g. noindex directive) on a QuinrelMeta record and dispatch re-indexing jobs.
     */
    public function resolveExclusion(QuinrelMeta $meta, ?string $canonicalUrl = null): bool
    {
        $targetUrl = $canonicalUrl !== null && trim($canonicalUrl) !== ''
            ? trim($canonicalUrl)
            : $meta->canonical_url;

        if (! is_string($targetUrl) || $targetUrl === '') {
            return false;
        }

        QuinrelMeta::withoutEvents(function () use ($meta, $targetUrl): void {
            $meta->update([
                'canonical_url' => $targetUrl,
                'robots' => 'index, follow',
            ]);
        });

        $this->dispatchResubmissionJobs($targetUrl);

        return true;
    }

    /**
     * Dispatch asynchronous resubmission jobs to Google Indexing API and IndexNow.
     */
    public function dispatchResubmissionJobs(string $url): void
    {
        if (trim($url) === '') {
            return;
        }

        PingGoogleIndexingJob::dispatch($url, 'URL_UPDATED');
        PingIndexNowJob::dispatch([$url]);
    }
}
