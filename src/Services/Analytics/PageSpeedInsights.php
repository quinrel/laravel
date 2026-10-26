<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Analytics;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Data\LighthouseAuditResult;
use Quinrel\Laravel\Support\UrlValidator;

class PageSpeedInsights
{
    private string $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * Fetch Core Web Vitals and Lighthouse diagnostics for a URL.
     * Cached for 7 days.
     *
     * @param  string  $strategy  'mobile' or 'desktop'
     * @return array<string, mixed>
     */
    public function fetch(string $url, string $strategy = 'mobile'): array
    {
        if (! UrlValidator::isSafe($url)) {
            return [];
        }

        $cacheKey = "quinrel.pagespeed.{$strategy}.".md5($url);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($url, $strategy) {
            $apiKey = config('quinrel.api.google_pagespeed_key');

            $queryParts = [
                'url='.urlencode($url),
                'strategy='.urlencode(strtoupper($strategy)),
                'category=performance',
                'category=seo',
                'category=best-practices',
            ];

            if (is_string($apiKey) && $apiKey !== '') {
                $queryParts[] = 'key='.urlencode($apiKey);
            }

            $requestUrl = $this->apiUrl.'?'.implode('&', $queryParts);

            /** @var Response $response */
            $response = Http::timeout(30)->get($requestUrl);

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();
            if (! is_array($data)) {
                return [];
            }

            $result = $this->parsePayload($url, $strategy, $data);

            return $result?->toArray() ?? [];
        });
    }

    /**
     * Analyze a URL (or raw PSI payload) into a strongly-typed LighthouseAuditResult DTO.
     *
     * @param  array<string, mixed>|null  $rawPayload
     */
    public function analyze(string $url, string $strategy = 'mobile', ?array $rawPayload = null): ?LighthouseAuditResult
    {
        if ($rawPayload !== null) {
            return $this->parsePayload($url, $strategy, $rawPayload);
        }

        $fetched = $this->fetch($url, $strategy);
        if ($fetched === []) {
            return null;
        }

        /** @var array{wasted_ms: int, items: array<int, array{url: string, total_bytes: int, wasted_ms: int}>} $renderBlocking */
        $renderBlocking = is_array($fetched['render_blocking_resources'] ?? null)
            ? $fetched['render_blocking_resources']
            : ['wasted_ms' => 0, 'items' => []];

        /** @var array{snippet: string, selector: string, node_label: string}|null $lcpElement */
        $lcpElement = is_array($fetched['lcp_element'] ?? null) ? $fetched['lcp_element'] : null;

        /** @var array<int, array{snippet: string, selector: string, score: float}> $layoutShiftElements */
        $layoutShiftElements = is_array($fetched['layout_shift_elements'] ?? null) ? $fetched['layout_shift_elements'] : [];

        /** @var array{wasted_bytes: int, wasted_ms: int, items: array<int, array{url: string, type: string, wasted_bytes: int, wasted_ms: int}>} $unminifiedAssets */
        $unminifiedAssets = is_array($fetched['unminified_assets'] ?? null)
            ? $fetched['unminified_assets']
            : ['wasted_bytes' => 0, 'wasted_ms' => 0, 'items' => []];

        /** @var array{wasted_bytes: int, wasted_ms: int, items: array<int, array{url: string, audit: string, wasted_bytes: int, wasted_ms: int}>} $unoptimizedImages */
        $unoptimizedImages = is_array($fetched['unoptimized_images'] ?? null)
            ? $fetched['unoptimized_images']
            : ['wasted_bytes' => 0, 'wasted_ms' => 0, 'items' => []];

        /** @var array<int, array{url: string, duration_ms: float, start_time_ms: float}> $longTasks */
        $longTasks = is_array($fetched['long_tasks'] ?? null) ? $fetched['long_tasks'] : [];

        /** @var array<int, array{id: string, title: string, wasted_ms: int, wasted_bytes: int}> $opportunities */
        $opportunities = is_array($fetched['opportunities'] ?? null) ? $fetched['opportunities'] : [];

        return new LighthouseAuditResult(
            url: (string) ($fetched['url'] ?? $url),
            strategy: (string) ($fetched['strategy'] ?? $strategy),
            performanceScore: isset($fetched['performance_score']) ? (int) $fetched['performance_score'] : (isset($fetched['score']) ? (int) $fetched['score'] : null),
            seoScore: isset($fetched['seo_score']) ? (int) $fetched['seo_score'] : null,
            bestPracticesScore: isset($fetched['best_practices_score']) ? (int) $fetched['best_practices_score'] : null,
            lcpMs: isset($fetched['lcp']) ? (float) $fetched['lcp'] : null,
            inpMs: isset($fetched['inp']) ? (float) $fetched['inp'] : null,
            clsScore: isset($fetched['cls']) ? (float) $fetched['cls'] : null,
            renderBlockingResources: $renderBlocking,
            lcpElement: $lcpElement,
            layoutShiftElements: $layoutShiftElements,
            unminifiedAssets: $unminifiedAssets,
            unoptimizedImages: $unoptimizedImages,
            longTasks: $longTasks,
            opportunities: $opportunities,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function parsePayload(string $url, string $strategy, array $data): ?LighthouseAuditResult
    {
        $hasOriginMetrics = isset($data['originLoadingExperience']['metrics']) && is_array($data['originLoadingExperience']['metrics']);
        $hasLighthouse = isset($data['lighthouseResult']) && is_array($data['lighthouseResult']);

        if (! $hasOriginMetrics && ! $hasLighthouse) {
            return null;
        }

        $metrics = $hasOriginMetrics ? $data['originLoadingExperience']['metrics'] : [];
        $lighthouse = $hasLighthouse ? $data['lighthouseResult'] : [];
        $categories = is_array($lighthouse['categories'] ?? null) ? $lighthouse['categories'] : [];
        $audits = is_array($lighthouse['audits'] ?? null) ? $lighthouse['audits'] : [];

        $lcp = $this->extractMetricValue($metrics, 'LARGEST_CONTENTFUL_PAINT_MS', $audits, 'largest-contentful-paint');
        $inp = $this->extractMetricValue($metrics, 'INTERACTION_TO_NEXT_PAINT', $audits, 'interaction-to-next-paint');
        $cls = $this->extractMetricValue($metrics, 'CUMULATIVE_LAYOUT_SHIFT_SCORE', $audits, 'cumulative-layout-shift');

        $perfScore = $this->normalizeScore($categories['performance']['score'] ?? null);
        $seoScore = $this->normalizeScore($categories['seo']['score'] ?? null);
        $bestPracticesScore = $this->normalizeScore($categories['best-practices']['score'] ?? null);

        $renderBlocking = $this->parseRenderBlocking($audits['render-blocking-resources'] ?? null);
        $lcpElement = $this->parseLcpElement($audits['largest-contentful-paint-element'] ?? null);
        $clsElements = $this->parseLayoutShiftElements($audits['layout-shift-elements'] ?? null);
        $unminified = $this->parseUnminifiedAssets(
            $audits['unminified-css'] ?? null,
            $audits['unminified-javascript'] ?? null
        );
        $unoptimizedImages = $this->parseUnoptimizedImages(
            $audits['uses-optimized-images'] ?? null,
            $audits['modern-image-formats'] ?? null
        );
        $longTasks = $this->parseLongTasks($audits['long-tasks'] ?? null);

        $opportunities = $this->compileTopOpportunities($renderBlocking, $unminified, $unoptimizedImages, $longTasks);

        return new LighthouseAuditResult(
            url: $url,
            strategy: strtolower($strategy),
            performanceScore: $perfScore,
            seoScore: $seoScore,
            bestPracticesScore: $bestPracticesScore,
            lcpMs: $lcp,
            inpMs: $inp,
            clsScore: $cls,
            renderBlockingResources: $renderBlocking,
            lcpElement: $lcpElement,
            layoutShiftElements: $clsElements,
            unminifiedAssets: $unminified,
            unoptimizedImages: $unoptimizedImages,
            longTasks: $longTasks,
            opportunities: $opportunities,
        );
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  array<string, mixed>  $audits
     */
    protected function extractMetricValue(array $metrics, string $cruxKey, array $audits, string $auditKey): ?float
    {
        if (isset($metrics[$cruxKey]['percentile']) && is_numeric($metrics[$cruxKey]['percentile'])) {
            return (float) $metrics[$cruxKey]['percentile'];
        }

        if (isset($audits[$auditKey]['numericValue']) && is_numeric($audits[$auditKey]['numericValue'])) {
            return (float) $audits[$auditKey]['numericValue'];
        }

        return null;
    }

    protected function normalizeScore(mixed $rawScore): ?int
    {
        if (! is_numeric($rawScore)) {
            return null;
        }

        $val = (float) $rawScore;
        if ($val <= 1.0) {
            return (int) round($val * 100);
        }

        return (int) round($val);
    }

    /**
     * @return array{wasted_ms: int, items: array<int, array{url: string, total_bytes: int, wasted_ms: int}>}
     */
    protected function parseRenderBlocking(mixed $audit): array
    {
        if (! is_array($audit)) {
            return ['wasted_ms' => 0, 'items' => []];
        }

        $details = is_array($audit['details'] ?? null) ? $audit['details'] : [];
        $rawItems = is_array($details['items'] ?? null) ? $details['items'] : [];
        $items = [];
        $sumWastedMs = 0;

        foreach ($rawItems as $item) {
            if (! is_array($item) || empty($item['url'])) {
                continue;
            }
            $wastedMs = (int) round((float) ($item['wastedMs'] ?? 0));
            $sumWastedMs += $wastedMs;
            $items[] = [
                'url' => (string) $item['url'],
                'total_bytes' => (int) ($item['totalBytes'] ?? 0),
                'wasted_ms' => $wastedMs,
            ];
        }

        $overallWastedMs = isset($details['overallSavingsMs']) && is_numeric($details['overallSavingsMs'])
            ? (int) round((float) $details['overallSavingsMs'])
            : $sumWastedMs;

        return [
            'wasted_ms' => $overallWastedMs,
            'items' => $items,
        ];
    }

    /**
     * @return array{snippet: string, selector: string, node_label: string}|null
     */
    protected function parseLcpElement(mixed $audit): ?array
    {
        if (! is_array($audit)) {
            return null;
        }

        $details = is_array($audit['details'] ?? null) ? $audit['details'] : [];
        $items = is_array($details['items'] ?? null) ? $details['items'] : [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            // Lighthouse v10+ may nest under items[].items[].node or directly under items[].node
            $node = is_array($item['node'] ?? null) ? $item['node'] : null;
            if ($node === null && is_array($item['items'] ?? null)) {
                foreach ($item['items'] as $subItem) {
                    if (is_array($subItem) && is_array($subItem['node'] ?? null)) {
                        $node = $subItem['node'];
                        break;
                    }
                }
            }

            if ($node !== null) {
                return [
                    'snippet' => (string) ($node['snippet'] ?? ''),
                    'selector' => (string) ($node['selector'] ?? ''),
                    'node_label' => (string) ($node['nodeLabel'] ?? ''),
                ];
            }
        }

        return null;
    }

    /**
     * @return array<int, array{snippet: string, selector: string, score: float}>
     */
    protected function parseLayoutShiftElements(mixed $audit): array
    {
        if (! is_array($audit)) {
            return [];
        }

        $details = is_array($audit['details'] ?? null) ? $audit['details'] : [];
        $rawItems = is_array($details['items'] ?? null) ? $details['items'] : [];
        $elements = [];

        foreach ($rawItems as $item) {
            if (! is_array($item)) {
                continue;
            }
            $node = is_array($item['node'] ?? null) ? $item['node'] : [];
            $snippet = (string) ($node['snippet'] ?? '');
            $selector = (string) ($node['selector'] ?? '');
            if ($snippet === '' && $selector === '') {
                continue;
            }

            $elements[] = [
                'snippet' => $snippet,
                'selector' => $selector,
                'score' => (float) ($item['score'] ?? 0.0),
            ];
        }

        return $elements;
    }

    /**
     * @return array{wasted_bytes: int, wasted_ms: int, items: array<int, array{url: string, type: string, wasted_bytes: int, wasted_ms: int}>}
     */
    protected function parseUnminifiedAssets(mixed $cssAudit, mixed $jsAudit): array
    {
        $items = [];
        $totalBytes = 0;
        $totalMs = 0;

        foreach (['css' => $cssAudit, 'javascript' => $jsAudit] as $type => $audit) {
            if (! is_array($audit)) {
                continue;
            }
            $details = is_array($audit['details'] ?? null) ? $audit['details'] : [];
            $totalBytes += (int) ($details['overallSavingsBytes'] ?? 0);
            $totalMs += (int) round((float) ($details['overallSavingsMs'] ?? 0));

            foreach ((array) ($details['items'] ?? []) as $item) {
                if (! is_array($item) || empty($item['url'])) {
                    continue;
                }
                $wastedBytes = (int) ($item['wastedBytes'] ?? 0);
                $wastedMs = (int) round((float) ($item['wastedMs'] ?? 0));
                $items[] = [
                    'url' => (string) $item['url'],
                    'type' => $type,
                    'wasted_bytes' => $wastedBytes,
                    'wasted_ms' => $wastedMs,
                ];
            }
        }

        if ($totalBytes === 0 && $items !== []) {
            $totalBytes = array_sum(array_column($items, 'wasted_bytes'));
        }

        return [
            'wasted_bytes' => $totalBytes,
            'wasted_ms' => $totalMs,
            'items' => $items,
        ];
    }

    /**
     * @return array{wasted_bytes: int, wasted_ms: int, items: array<int, array{url: string, audit: string, wasted_bytes: int, wasted_ms: int}>}
     */
    protected function parseUnoptimizedImages(mixed $optimizedAudit, mixed $modernFormatAudit): array
    {
        $items = [];
        $totalBytes = 0;
        $totalMs = 0;

        foreach ([
            'uses-optimized-images' => $optimizedAudit,
            'modern-image-formats' => $modernFormatAudit,
        ] as $auditId => $audit) {
            if (! is_array($audit)) {
                continue;
            }
            $details = is_array($audit['details'] ?? null) ? $audit['details'] : [];
            $totalBytes += (int) ($details['overallSavingsBytes'] ?? 0);
            $totalMs += (int) round((float) ($details['overallSavingsMs'] ?? 0));

            foreach ((array) ($details['items'] ?? []) as $item) {
                if (! is_array($item) || empty($item['url'])) {
                    continue;
                }
                $items[] = [
                    'url' => (string) $item['url'],
                    'audit' => $auditId,
                    'wasted_bytes' => (int) ($item['wastedBytes'] ?? 0),
                    'wasted_ms' => (int) round((float) ($item['wastedMs'] ?? 0)),
                ];
            }
        }

        if ($totalBytes === 0 && $items !== []) {
            $totalBytes = array_sum(array_column($items, 'wasted_bytes'));
        }

        return [
            'wasted_bytes' => $totalBytes,
            'wasted_ms' => $totalMs,
            'items' => $items,
        ];
    }

    /**
     * @return array<int, array{url: string, duration_ms: float, start_time_ms: float}>
     */
    protected function parseLongTasks(mixed $audit): array
    {
        if (! is_array($audit)) {
            return [];
        }

        $details = is_array($audit['details'] ?? null) ? $audit['details'] : [];
        $tasks = [];

        foreach ((array) ($details['items'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $duration = (float) ($item['duration'] ?? 0.0);
            if ($duration < 50.0) {
                continue;
            }
            $tasks[] = [
                'url' => (string) ($item['url'] ?? 'main-thread'),
                'duration_ms' => $duration,
                'start_time_ms' => (float) ($item['startTime'] ?? 0.0),
            ];
        }

        return $tasks;
    }

    /**
     * @param  array{wasted_ms: int, items: array<int, mixed>}  $renderBlocking
     * @param  array{wasted_bytes: int, wasted_ms: int, items: array<int, mixed>}  $unminified
     * @param  array{wasted_bytes: int, wasted_ms: int, items: array<int, mixed>}  $unoptimizedImages
     * @param  array<int, array{url: string, duration_ms: float, start_time_ms: float}>  $longTasks
     * @return array<int, array{id: string, title: string, wasted_ms: int, wasted_bytes: int}>
     */
    protected function compileTopOpportunities(
        array $renderBlocking,
        array $unminified,
        array $unoptimizedImages,
        array $longTasks
    ): array {
        $opportunities = [];

        if ($renderBlocking['wasted_ms'] > 0 || $renderBlocking['items'] !== []) {
            $opportunities[] = [
                'id' => 'render-blocking-resources',
                'title' => "Save {$renderBlocking['wasted_ms']}ms by eliminating render-blocking CSS/JS",
                'wasted_ms' => $renderBlocking['wasted_ms'],
                'wasted_bytes' => 0,
            ];
        }

        if ($unoptimizedImages['wasted_bytes'] > 0 || $unoptimizedImages['items'] !== []) {
            $kb = (int) round($unoptimizedImages['wasted_bytes'] / 1024);
            $opportunities[] = [
                'id' => 'uses-optimized-images',
                'title' => "Save {$kb} KB ({$unoptimizedImages['wasted_ms']}ms) by serving modern, compressed images",
                'wasted_ms' => max($unoptimizedImages['wasted_ms'], (int) round($kb * 2)),
                'wasted_bytes' => $unoptimizedImages['wasted_bytes'],
            ];
        }

        if ($unminified['wasted_bytes'] > 0 || $unminified['items'] !== []) {
            $kb = (int) round($unminified['wasted_bytes'] / 1024);
            $opportunities[] = [
                'id' => 'unminified-assets',
                'title' => "Save {$kb} KB ({$unminified['wasted_ms']}ms) by minifying CSS & JavaScript bundles",
                'wasted_ms' => max($unminified['wasted_ms'], $kb),
                'wasted_bytes' => $unminified['wasted_bytes'],
            ];
        }

        if ($longTasks !== []) {
            $totalLongTaskMs = (int) round(array_sum(array_column($longTasks, 'duration_ms')));
            $opportunities[] = [
                'id' => 'long-tasks',
                'title' => "Reduce {$totalLongTaskMs}ms of main-thread blocking long tasks",
                'wasted_ms' => $totalLongTaskMs,
                'wasted_bytes' => 0,
            ];
        }

        usort($opportunities, fn (array $a, array $b): int => $b['wasted_ms'] <=> $a['wasted_ms']);

        return array_slice($opportunities, 0, 5);
    }
}
