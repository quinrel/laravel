<?php

declare(strict_types=1);

namespace Holvek\Laravel\Services\Analytics;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PageSpeedInsights
{
    private string $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * Fetch Core Web Vitals for a URL.
     * Cached for 7 days.
     *
     * @param  string  $strategy  'mobile' or 'desktop'
     * @return array<string, mixed>
     */
    public function fetch(string $url, string $strategy = 'mobile'): array
    {
        $cacheKey = "holvek.pagespeed.{$strategy}.".md5($url);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($url, $strategy) {
            $apiKey = config('holvek.api.google_pagespeed_key');

            $params = [
                'url' => $url,
                'strategy' => strtoupper($strategy),
                'category' => 'performance',
            ];

            if ($apiKey) {
                $params['key'] = $apiKey;
            }

            /** @var Response $response */
            $response = Http::timeout(30)->get($this->apiUrl, $params);

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();
            if (! is_array($data) || ! isset($data['originLoadingExperience']['metrics'])) {
                return [];
            }

            $metrics = $data['originLoadingExperience']['metrics'];

            return [
                'lcp' => $metrics['LARGEST_CONTENTFUL_PAINT_MS']['percentile'] ?? null,
                'inp' => $metrics['INTERACTION_TO_NEXT_PAINT']['percentile'] ?? null,
                'cls' => $metrics['CUMULATIVE_LAYOUT_SHIFT_SCORE']['percentile'] ?? null,
                'score' => $data['lighthouseResult']['categories']['performance']['score'] ?? null,
            ];
        });
    }
}
