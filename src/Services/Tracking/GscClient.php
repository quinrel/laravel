<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Tracking;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Services\GoogleSearchConsoleService;

class GscClient
{
    private string $searchConsoleApiUrl = 'https://searchconsole.googleapis.com/webmasters/v3';

    public function __construct(
        protected GoogleAlgorithmUpdates $algorithmUpdates,
        protected ?GoogleSearchConsoleService $gscService = null
    ) {
        $this->gscService ??= app(GoogleSearchConsoleService::class);
    }

    protected function getToken(): ?string
    {
        return $this->gscService?->resolveAccessToken('https://www.googleapis.com/auth/webmasters');
    }

    protected function getSiteUrl(): string
    {
        $siteUrl = (string) (config('quinrel.gsc.site_url') ?: config('app.url'));

        return urlencode($siteUrl);
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
