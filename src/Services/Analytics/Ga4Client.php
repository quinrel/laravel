<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Analytics;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Ga4Client
{
    /**
     * @return array<string, mixed>
     */
    public function getSessionTrends(int $days = 30): array
    {
        return $this->runReport([
            'dateRanges' => [['startDate' => "{$days}daysAgo", 'endDate' => 'today']],
            'dimensions' => [['name' => 'date']],
            'metrics' => [['name' => 'sessions']],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTopLandingPages(int $days = 30, int $limit = 10): array
    {
        return $this->runReport([
            'dateRanges' => [['startDate' => "{$days}daysAgo", 'endDate' => 'today']],
            'dimensions' => [['name' => 'landingPagePlusQueryString']],
            'metrics' => [['name' => 'sessions']],
            'limit' => $limit,
        ]);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function runReport(array $body): array
    {
        $propertyId = config('quinrel.analytics.ga4_property_id');
        $token = config('quinrel.analytics.ga4_access_token'); // Expected to be provided by host app or OAuth

        if (! $propertyId || ! $token) {
            return [];
        }

        $cacheKey = 'quinrel.ga4.'.md5(json_encode($body) ?: '');

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($propertyId, $token, $body) {
            /** @var Response $response */
            $response = Http::withToken((string) $token)
                ->timeout(15)
                ->post("https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport", $body);

            if ($response->failed()) {
                return [];
            }

            $json = $response->json();

            return is_array($json) ? $json : [];
        });
    }
}
