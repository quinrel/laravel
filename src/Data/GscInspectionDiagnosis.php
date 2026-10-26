<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Data;

use Carbon\CarbonInterface;

final readonly class GscInspectionDiagnosis
{
    /**
     * @param  array<int, string>  $referringUrls
     * @param  array<int, string>  $sitemaps
     */
    public function __construct(
        public string $url,
        public string $verdict,
        public string $coverageState,
        public string $indexingState,
        public string $robotsTxtState,
        public ?string $userCanonical,
        public ?string $googleCanonical,
        public bool $hasCanonicalMismatch,
        public string $crawledAs,
        public ?CarbonInterface $lastCrawlTime,
        public string $pageFetchState = 'PAGE_FETCH_STATE_UNSPECIFIED',
        public array $referringUrls = [],
        public array $sitemaps = [],
    ) {}

    public function isIndexed(): bool
    {
        return $this->verdict === 'PASS';
    }

    public function isBlockedByRobotsTxt(): bool
    {
        return $this->robotsTxtState === 'DISALLOWED'
            || str_contains(strtolower($this->coverageState), 'robots.txt')
            || $this->pageFetchState === 'BLOCKED_ROBOTS_TXT';
    }

    public function isBlockedByNoindex(): bool
    {
        return in_array($this->indexingState, ['BLOCKED_BY_META_TAG', 'BLOCKED_BY_HTTP_HEADER', 'BLOCKED_BY_CUSTOM_HEADER'], true)
            || str_contains(strtolower($this->coverageState), 'noindex');
    }

    public function isCrawledNotIndexed(): bool
    {
        $coverageLower = strtolower($this->coverageState);

        return str_contains($coverageLower, 'crawled - currently not indexed')
            || str_contains($coverageLower, 'discovered - currently not indexed');
    }

    public function isNotFoundOrSoft404(): bool
    {
        $coverageLower = strtolower($this->coverageState);

        return in_array($this->pageFetchState, ['NOT_FOUND', 'SOFT_404'], true)
            || str_contains($coverageLower, '404');
    }

    public function isRedirectPage(): bool
    {
        return str_contains(strtolower($this->coverageState), 'redirect');
    }

    public function healthBadgeColor(): string
    {
        if ($this->isIndexed() && ! $this->hasCanonicalMismatch) {
            return 'green';
        }

        if ($this->hasCanonicalMismatch || $this->verdict === 'NEUTRAL') {
            return 'yellow';
        }

        return 'red';
    }

    public function healthLabel(): string
    {
        return match ($this->healthBadgeColor()) {
            'green' => 'Indexed',
            'yellow' => $this->hasCanonicalMismatch ? 'Canonical Mismatch' : 'Excluded / Warning',
            default => 'Blocked / Error',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'verdict' => $this->verdict,
            'coverage_state' => $this->coverageState,
            'indexing_state' => $this->indexingState,
            'robots_txt_state' => $this->robotsTxtState,
            'user_canonical' => $this->userCanonical,
            'google_canonical' => $this->googleCanonical,
            'has_canonical_mismatch' => $this->hasCanonicalMismatch,
            'crawled_as' => $this->crawledAs,
            'last_crawl_time' => $this->lastCrawlTime?->toIso8601String(),
            'page_fetch_state' => $this->pageFetchState,
            'referring_urls' => $this->referringUrls,
            'sitemaps' => $this->sitemaps,
            'health_badge' => $this->healthBadgeColor(),
            'health_label' => $this->healthLabel(),
            'is_indexed' => $this->isIndexed(),
        ];
    }
}
