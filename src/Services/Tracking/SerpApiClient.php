<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Tracking;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SerpApiClient
{
    private string $apiUrl = 'https://serpapi.com/search';

    /**
     * @return array<string, mixed>
     */
    public function getRanking(string $keyword, string $targetUrl): array
    {
        $apiKey = config('quinrel.api.serpapi_key');
        if (! $apiKey) {
            return [];
        }

        /** @var Response $response */
        $response = Http::timeout(30)->get($this->apiUrl, [
            'q' => $keyword,
            'api_key' => $apiKey,
            'engine' => 'google',
            'num' => 100,
        ]);

        if ($response->failed()) {
            return [];
        }

        $data = $response->json();
        if (! is_array($data) || ! isset($data['organic_results'])) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $results */
        $results = $data['organic_results'];

        foreach ($results as $result) {
            if (isset($result['link']) && is_string($result['link']) && str_contains($result['link'], $targetUrl)) {
                return [
                    'rank' => $result['position'] ?? null,
                    'url' => $result['link'],
                ];
            }
        }

        return ['rank' => null, 'url' => null];
    }
}
