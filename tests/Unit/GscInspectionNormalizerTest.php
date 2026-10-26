<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Unit;

use Carbon\CarbonInterface;
use Quinrel\Laravel\Services\Tracking\GscInspectionNormalizer;
use Quinrel\Laravel\Tests\TestCase;

class GscInspectionNormalizerTest extends TestCase
{
    public function test_normalizes_submitted_and_indexed_payload(): void
    {
        $normalizer = new GscInspectionNormalizer;

        $payload = [
            'inspectionResult' => [
                'indexStatusResult' => [
                    'verdict' => 'PASS',
                    'coverageState' => 'Submitted and indexed',
                    'robotsTxtState' => 'ALLOWED',
                    'indexingState' => 'INDEXING_ALLOWED',
                    'lastCrawlTime' => '2026-10-20T14:30:00Z',
                    'pageFetchState' => 'SUCCESSFUL',
                    'googleCanonical' => 'https://example.com/guide',
                    'userCanonical' => 'https://example.com/guide',
                    'crawledAs' => 'MOBILE',
                    'sitemap' => ['https://example.com/sitemap.xml'],
                    'referringUrls' => ['https://example.com/'],
                ],
            ],
        ];

        $diagnosis = $normalizer->normalize('https://example.com/guide', $payload);

        $this->assertTrue($diagnosis->isIndexed());
        $this->assertFalse($diagnosis->hasCanonicalMismatch);
        $this->assertSame('PASS', $diagnosis->verdict);
        $this->assertSame('Submitted and indexed', $diagnosis->coverageState);
        $this->assertSame('INDEXING_ALLOWED', $diagnosis->indexingState);
        $this->assertSame('ALLOWED', $diagnosis->robotsTxtState);
        $this->assertSame('MOBILE', $diagnosis->crawledAs);
        $this->assertInstanceOf(CarbonInterface::class, $diagnosis->lastCrawlTime);
        $this->assertSame('green', $diagnosis->healthBadgeColor());
        $this->assertSame('Indexed', $diagnosis->healthLabel());
    }

    public function test_detects_canonical_mismatch_and_excluded_states(): void
    {
        $normalizer = new GscInspectionNormalizer;

        $mismatchPayload = [
            'inspectionResult' => [
                'indexStatusResult' => [
                    'verdict' => 'NEUTRAL',
                    'coverageState' => 'Duplicate, Google chose different canonical than user',
                    'robotsTxtState' => 'ALLOWED',
                    'indexingState' => 'INDEXING_ALLOWED',
                    'userCanonical' => 'https://example.com/products?sort=asc',
                    'googleCanonical' => 'https://example.com/products',
                    'crawledAs' => 'MOBILE',
                ],
            ],
        ];

        $diagnosis = $normalizer->normalize('https://example.com/products?sort=asc', $mismatchPayload);

        $this->assertTrue($diagnosis->hasCanonicalMismatch);
        $this->assertSame('yellow', $diagnosis->healthBadgeColor());
        $this->assertSame('Canonical Mismatch', $diagnosis->healthLabel());

        $blockedPayload = [
            'inspectionResult' => [
                'indexStatusResult' => [
                    'verdict' => 'FAIL',
                    'coverageState' => 'Blocked by robots.txt',
                    'robotsTxtState' => 'DISALLOWED',
                    'indexingState' => 'INDEXING_ALLOWED',
                    'pageFetchState' => 'BLOCKED_ROBOTS_TXT',
                ],
            ],
        ];

        $blocked = $normalizer->normalize('https://example.com/private/report', $blockedPayload);
        $this->assertTrue($blocked->isBlockedByRobotsTxt());
        $this->assertSame('red', $blocked->healthBadgeColor());
    }
}
