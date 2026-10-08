<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Tracking;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GscClient
{
    private string $searchConsoleApiUrl = 'https://searchconsole.googleapis.com/webmasters/v3';

    private string $indexingApiUrl = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    public function __construct(
        protected GoogleAlgorithmUpdates $algorithmUpdates
    ) {}

    protected function getToken(): ?string
    {
        return config('holvek.api.google_oauth_token');
    }

    protected function getSiteUrl(): string
    {
        return urlencode((string) config('app.url'));
    }

    /**
     * @return array<string, mixed>
     */
    public function submitUrl(string $url, string $type = 'URL_UPDATED'): array
    {
        $token = $this->getToken();
        if (! $token) {
            return [];
        }

        /** @var Response $response */
        $response = Http::withToken($token)
            ->timeout(15)
            ->post($this->indexingApiUrl, [
                'url' => $url,
                'type' => $type,
            ]);

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function submitSitemap(string $feedpath): array
    {
        $token = $this->getToken();
        $siteUrl = $this->getSiteUrl();

        if (! $token || ! $siteUrl) {
            return [];
        }

        $url = "{$this->searchConsoleApiUrl}/sites/{$siteUrl}/sitemaps/".urlencode($feedpath);

        /** @var Response $response */
        $response = Http::withToken($token)->timeout(15)->put($url);

        return ['status' => $response->status()];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAnalytics(int $days = 30): array
    {
        $token = $this->getToken();
        $siteUrl = $this->getSiteUrl();

        if (! $token || ! $siteUrl) {
            return [];
        }

        $url = "{$this->searchConsoleApiUrl}/sites/{$siteUrl}/searchAnalytics/query";

        $start = now()->subDays($days);
        $end = now();

        /** @var Response $response */
        $response = Http::withToken($token)->timeout(30)->post($url, [
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'dimensions' => ['date'],
        ]);

        $json = $response->json();
        $data = is_array($json) ? $json : [];

        $data['algorithm_updates'] = $this->algorithmUpdates->getOverlaysForPeriod($start, $end);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function inspectUrl(string $url): array
    {
        $token = $this->getToken();
        $siteUrl = urldecode($this->getSiteUrl());

        if (! $token || ! $siteUrl) {
            return [];
        }

        $endpoint = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';

        /** @var Response $response */
        $response = Http::withToken($token)->timeout(30)->post($endpoint, [
            'inspectionUrl' => $url,
            'siteUrl' => rtrim($siteUrl, '/').'/',
        ]);

        $json = $response->json();

        return is_array($json) ? $json : [];
    }
}
