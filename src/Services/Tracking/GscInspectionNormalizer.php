<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Tracking;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Quinrel\Laravel\Data\GscInspectionDiagnosis;
use Throwable;

class GscInspectionNormalizer
{
    /**
     * Normalize a raw Google Search Console URL Inspection API payload into a strongly-typed DTO.
     *
     * @param  array<string, mixed>  $payload
     */
    public function normalize(string $url, array $payload): GscInspectionDiagnosis
    {
        $inspection = is_array($payload['inspectionResult'] ?? null)
            ? $payload['inspectionResult']
            : $payload;

        $indexStatus = is_array($inspection['indexStatusResult'] ?? null)
            ? $inspection['indexStatusResult']
            : [];

        $verdict = $this->normalizeString($indexStatus['verdict'] ?? null, 'VERDICT_UNSPECIFIED');
        $coverageState = $this->normalizeString($indexStatus['coverageState'] ?? null, 'Unknown');
        $indexingState = $this->normalizeString($indexStatus['indexingState'] ?? null, 'INDEXING_STATE_UNSPECIFIED');
        $robotsTxtState = $this->normalizeString($indexStatus['robotsTxtState'] ?? null, 'ROBOTS_TXT_STATE_UNSPECIFIED');
        $crawledAs = $this->normalizeString($indexStatus['crawledAs'] ?? null, 'CRAWLING_USER_AGENT_UNSPECIFIED');
        $pageFetchState = $this->normalizeString($indexStatus['pageFetchState'] ?? null, 'PAGE_FETCH_STATE_UNSPECIFIED');

        $userCanonical = $this->normalizeNullableUrl($indexStatus['userCanonical'] ?? null);
        $googleCanonical = $this->normalizeNullableUrl($indexStatus['googleCanonical'] ?? null);

        $hasCanonicalMismatch = $this->detectCanonicalMismatch($url, $userCanonical, $googleCanonical, $coverageState);
        $lastCrawlTime = $this->parseTimestamp($indexStatus['lastCrawlTime'] ?? null);
        $referringUrls = $this->normalizeStringList($indexStatus['referringUrls'] ?? []);
        $sitemaps = $this->normalizeStringList($indexStatus['sitemap'] ?? []);

        return new GscInspectionDiagnosis(
            url: $url,
            verdict: $verdict,
            coverageState: $coverageState,
            indexingState: $indexingState,
            robotsTxtState: $robotsTxtState,
            userCanonical: $userCanonical,
            googleCanonical: $googleCanonical,
            hasCanonicalMismatch: $hasCanonicalMismatch,
            crawledAs: $crawledAs,
            lastCrawlTime: $lastCrawlTime,
            pageFetchState: $pageFetchState,
            referringUrls: $referringUrls,
            sitemaps: $sitemaps,
        );
    }

    protected function detectCanonicalMismatch(
        string $inspectedUrl,
        ?string $userCanonical,
        ?string $googleCanonical,
        string $coverageState
    ): bool {
        $coverageLower = strtolower($coverageState);
        if (
            str_contains($coverageLower, 'google chose different canonical')
            || str_contains($coverageLower, 'duplicate without user-selected canonical')
        ) {
            return true;
        }

        if ($googleCanonical === null) {
            return false;
        }

        $declared = $userCanonical ?? $inspectedUrl;

        return $this->canonicalFingerprint($declared) !== $this->canonicalFingerprint($googleCanonical);
    }

    protected function canonicalFingerprint(string $url): string
    {
        return rtrim(trim($url), '/');
    }

    protected function normalizeString(mixed $value, string $default): string
    {
        if (! is_string($value)) {
            return $default;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : $default;
    }

    protected function normalizeNullableUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    protected function parseTimestamp(mixed $value): ?CarbonInterface
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeStringList(mixed $items): array
    {
        if (is_string($items) && trim($items) !== '') {
            return [trim($items)];
        }

        if (! is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (is_string($item) && trim($item) !== '') {
                $normalized[] = trim($item);
            }
        }

        return array_values(array_unique($normalized));
    }
}
