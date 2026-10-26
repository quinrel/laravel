<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Technical;

use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Analysis\InternalLinkSuggester;

class SeoCrawlActionPlanService
{
    public function __construct(
        protected ?InternalLinkSuggester $linkSuggester = null,
    ) {
        $this->linkSuggester ??= app(InternalLinkSuggester::class);
    }

    /**
     * Aggregate findings from a SiteCrawlOrchestrator crawl into prioritized P0–P3 remediation tickets.
     *
     * @param  array{
     *     total_crawled: int,
     *     pages: array<string, array<string, mixed>>,
     *     duplicate_canonicals: array<string, array<int, string>>,
     *     orphaned_urls: array<int, string>,
     *     redirect_chains: array<int, array<string, mixed>>,
     *     redirect_loops: array<int, array<string, mixed>>
     * }  $crawlResult
     * @return array{
     *     score: int,
     *     total_crawled: int,
     *     counts: array{P0: int, P1: int, P2: int, P3: int},
     *     tickets: array<int, array{
     *         priority: string,
     *         severity: string,
     *         category: string,
     *         url: string,
     *         title: string,
     *         description: string,
     *         remediation: string,
     *         autofix_action: ?string,
     *         metadata: array<string, mixed>
     *     }>
     * }
     */
    public function buildActionPlan(array $crawlResult): array
    {
        $tickets = [];
        $pages = $crawlResult['pages'];

        // 1. P0 (Critical): Redirect Loops
        foreach ($crawlResult['redirect_loops'] as $loop) {
            $source = (string) ($loop['source_url'] ?? '');
            $tickets[] = [
                'priority' => 'P0',
                'severity' => 'Critical',
                'category' => 'redirect_loop',
                'url' => $source,
                'title' => 'Circular Redirect Loop Detected',
                'description' => "Redirect from `{$source}` forms an infinite loop (`".implode(' -> ', (array) ($loop['chain'] ?? [])).'`).',
                'remediation' => 'Break the circular redirect chain by pointing the source URL directly to a live 200 OK destination.',
                'autofix_action' => null,
                'metadata' => $loop,
            ];
        }

        // 2. P0 (Critical): Duplicate Canonicals
        foreach ($crawlResult['duplicate_canonicals'] as $canonicalUrl => $sharingUrls) {
            $tickets[] = [
                'priority' => 'P0',
                'severity' => 'Critical',
                'category' => 'duplicate_canonical',
                'url' => (string) $canonicalUrl,
                'title' => 'Duplicate Canonical Tag Across Multiple Pages',
                'description' => count($sharingUrls).' distinct URLs declare the same canonical URL (`'.$canonicalUrl.'`).',
                'remediation' => 'Ensure each unique page has a self-referencing canonical URL unless intentionally consolidating duplicates.',
                'autofix_action' => null,
                'metadata' => ['affected_urls' => $sharingUrls],
            ];
        }

        // 3. P1 (High): Redirect Chains
        foreach ($crawlResult['redirect_chains'] as $chain) {
            $source = (string) ($chain['source_url'] ?? '');
            $hops = (array) ($chain['chain'] ?? []);
            $finalTarget = $hops !== [] ? (string) end($hops) : (string) ($chain['target_url'] ?? '');
            $tickets[] = [
                'priority' => 'P1',
                'severity' => 'High',
                'category' => 'redirect_chain',
                'url' => $source,
                'title' => 'Multi-Hop Redirect Chain',
                'description' => "Redirect from `{$source}` passes through multiple hops before reaching `{$finalTarget}`.",
                'remediation' => "Flatten the redirect so `{$source}` points directly to `{$finalTarget}`.",
                'autofix_action' => 'flatten_redirect_chain',
                'metadata' => [
                    'source_url' => $source,
                    'final_target' => $finalTarget,
                    'chain' => $hops,
                ],
            ];
        }

        // 4. P1 (High): Orphaned Content
        $orphanedSet = array_flip($crawlResult['orphaned_urls']);
        foreach ($crawlResult['orphaned_urls'] as $orphanedUrl) {
            $tickets[] = [
                'priority' => 'P1',
                'severity' => 'High',
                'category' => 'orphaned_content',
                'url' => (string) $orphanedUrl,
                'title' => 'Orphaned Page (Zero Internal Inbound Links)',
                'description' => "No internal pages link to `{$orphanedUrl}`, making discovery and PageRank flow difficult.",
                'remediation' => 'Add contextual internal `<a href>` links from related cornerstone articles or category pages.',
                'autofix_action' => null,
                'metadata' => [],
            ];
        }

        // 5. Per-page checks (P0, P1, P2, P3)
        foreach ($pages as $url => $page) {
            $status = (int) ($page['status'] ?? 0);

            if ($status >= 400 || $status === 0) {
                $tickets[] = [
                    'priority' => 'P0',
                    'severity' => 'Critical',
                    'category' => 'http_error',
                    'url' => $url,
                    'title' => $status === 404 ? 'Internal 404 Not Found' : "HTTP {$status} Error",
                    'description' => "Crawling `{$url}` returned status {$status}".(! empty($page['error']) ? " ({$page['error']})" : '').'.',
                    'remediation' => $status === 404
                        ? 'Create a 301 redirect to the closest valid URL or update broken internal links referencing this URL.'
                        : 'Resolve the server or transport error so search bots receive a 200 OK response.',
                    'autofix_action' => $status === 404 ? 'create_suggested_redirect' : null,
                    'metadata' => ['status' => $status],
                ];

                continue;
            }

            if ($status >= 300) {
                continue;
            }

            // P0: Missing <title> tag
            $title = $page['title'] ?? null;
            $titleLen = (int) ($page['title_length'] ?? 0);
            if ($title === null || $titleLen === 0) {
                $tickets[] = [
                    'priority' => 'P0',
                    'severity' => 'Critical',
                    'category' => 'missing_title',
                    'url' => $url,
                    'title' => 'Missing <title> Tag',
                    'description' => "Page `{$url}` is missing an HTML `<title>` element.",
                    'remediation' => 'Define a descriptive SEO title (30–60 characters) via `QuinrelMeta` or `Quinrel::setTitle()`.',
                    'autofix_action' => 'autofix_meta',
                    'metadata' => [],
                ];
            } elseif ($titleLen < 10 || $titleLen > 60) {
                // P2: Title length violation
                $tickets[] = [
                    'priority' => 'P2',
                    'severity' => 'Medium',
                    'category' => 'title_length',
                    'url' => $url,
                    'title' => 'Title Length Outside Optimal Range',
                    'description' => "Title length is {$titleLen} characters (recommended: 10–60 characters).",
                    'remediation' => 'Adjust the page title to stay between 10 and 60 characters to prevent SERP truncation.',
                    'autofix_action' => 'autofix_meta',
                    'metadata' => ['length' => $titleLen],
                ];
            }

            // P1: Missing H1 heading
            $h1Count = (int) ($page['h1_count'] ?? 0);
            if ($h1Count === 0) {
                $tickets[] = [
                    'priority' => 'P1',
                    'severity' => 'High',
                    'category' => 'missing_h1',
                    'url' => $url,
                    'title' => 'Missing <h1> Heading',
                    'description' => "Page `{$url}` does not contain a primary `<h1>` heading.",
                    'remediation' => 'Add a single descriptive `<h1>` heading aligned with the page target keyword.',
                    'autofix_action' => null,
                    'metadata' => [],
                ];
            }

            // P2: Meta description missing or length violation
            $desc = $page['description'] ?? null;
            $descLen = (int) ($page['description_length'] ?? 0);
            if ($desc === null || $descLen < 50 || $descLen > 160) {
                $tickets[] = [
                    'priority' => 'P2',
                    'severity' => 'Medium',
                    'category' => 'description_length',
                    'url' => $url,
                    'title' => $desc === null ? 'Missing Meta Description' : 'Meta Description Length Violation',
                    'description' => $desc === null
                        ? "Page `{$url}` has no `<meta name=\"description\">` tag."
                        : "Meta description length is {$descLen} characters (recommended: 50–160 characters).",
                    'remediation' => 'Provide a compelling meta description between 50 and 160 characters.',
                    'autofix_action' => 'autofix_meta',
                    'metadata' => ['length' => $descLen],
                ];
            }

            // P2: Missing apple-touch-icon
            if (empty($page['has_apple_touch_icon'])) {
                $tickets[] = [
                    'priority' => 'P2',
                    'severity' => 'Medium',
                    'category' => 'missing_apple_touch_icon',
                    'url' => $url,
                    'title' => 'Missing apple-touch-icon',
                    'description' => "Page `{$url}` lacks a `<link rel=\"apple-touch-icon\">` tag.",
                    'remediation' => 'Add `<link rel="apple-touch-icon" href="/apple-touch-icon.png">` to your document `<head>`.',
                    'autofix_action' => null,
                    'metadata' => [],
                ];
            }

            // P2: Missing HSTS header
            if (empty($page['has_hsts'])) {
                $tickets[] = [
                    'priority' => 'P2',
                    'severity' => 'Medium',
                    'category' => 'missing_hsts',
                    'url' => $url,
                    'title' => 'Missing Strict-Transport-Security (HSTS) Header',
                    'description' => "Response from `{$url}` does not include a `Strict-Transport-Security` header.",
                    'remediation' => 'Configure `Strict-Transport-Security: max-age=31536000; includeSubDomains` at your web server or middleware layer.',
                    'autofix_action' => null,
                    'metadata' => [],
                ];
            }

            // P2: Low word count
            $wordCount = (int) ($page['word_count'] ?? 0);
            if ($wordCount < 150) {
                $tickets[] = [
                    'priority' => 'P2',
                    'severity' => 'Medium',
                    'category' => 'low_word_count',
                    'url' => $url,
                    'title' => 'Thin Content / Low Word Count',
                    'description' => "Page `{$url}` contains only {$wordCount} words (recommended minimum: 150 words).",
                    'remediation' => 'Expand page copy with comprehensive, authoritative topic coverage.',
                    'autofix_action' => null,
                    'metadata' => ['word_count' => $wordCount],
                ];
            }

            // P3: Internal linking opportunities
            $meta = QuinrelMeta::query()->where('canonical_url', $url)->first();
            $focusKeyword = (string) ($meta?->target_keyword ?: $title ?: '');
            if ($focusKeyword !== '' && $this->linkSuggester !== null) {
                $suggestions = array_values(array_filter(
                    $this->linkSuggester->suggest(implode(' ', (array) ($page['internal_links'] ?? [])), $focusKeyword),
                    fn (array $s): bool => $s['url'] !== $url
                ));

                if ($suggestions !== [] || isset($orphanedSet[$url])) {
                    $tickets[] = [
                        'priority' => 'P3',
                        'severity' => 'Low',
                        'category' => 'internal_linking_opportunity',
                        'url' => $url,
                        'title' => 'Internal Linking Opportunity',
                        'description' => count($suggestions).' relevant internal pages match keyword `'.$focusKeyword.'`.',
                        'remediation' => 'Add contextual anchor links to related internal URLs to strengthen topical cluster authority.',
                        'autofix_action' => null,
                        'metadata' => ['suggestions' => $suggestions],
                    ];
                }
            }
        }

        $priorityOrder = ['P0' => 0, 'P1' => 1, 'P2' => 2, 'P3' => 3];
        usort(
            $tickets,
            fn (array $a, array $b): int => $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']]
        );

        $counts = ['P0' => 0, 'P1' => 0, 'P2' => 0, 'P3' => 0];
        foreach ($tickets as $ticket) {
            $counts[$ticket['priority']]++;
        }

        $score = $this->calculateHealthScore($crawlResult['total_crawled'], $counts);

        return [
            'score' => $score,
            'total_crawled' => $crawlResult['total_crawled'],
            'counts' => $counts,
            'tickets' => $tickets,
        ];
    }

    /**
     * @param  array{P0: int, P1: int, P2: int, P3: int}  $counts
     */
    protected function calculateHealthScore(int $totalCrawled, array $counts): int
    {
        if ($totalCrawled === 0) {
            return 100;
        }

        $deductions = ($counts['P0'] * 20) + ($counts['P1'] * 10) + ($counts['P2'] * 3) + ($counts['P3'] * 1);
        $normalizedDeduction = (int) round($deductions / max(1, sqrt($totalCrawled)));

        return max(0, min(100, 100 - $normalizedDeduction));
    }
}
