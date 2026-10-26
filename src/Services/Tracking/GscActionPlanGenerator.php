<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Tracking;

use Quinrel\Laravel\Data\GscInspectionDiagnosis;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Models\QuinrelRedirect;
use Quinrel\Laravel\Services\Analysis\InternalLinkSuggester;
use Quinrel\Laravel\Services\RobotsTxtManager;

class GscActionPlanGenerator
{
    public function __construct(
        protected ?RobotsTxtManager $robotsManager = null,
        protected ?InternalLinkSuggester $linkSuggester = null,
    ) {
        $this->robotsManager ??= app(RobotsTxtManager::class);
        $this->linkSuggester ??= app(InternalLinkSuggester::class);
    }

    /**
     * Translate a normalized GSC inspection diagnosis into prioritized developer remediation actions.
     *
     * @return array<int, array{
     *     id: string,
     *     severity: string,
     *     priority: int,
     *     title: string,
     *     description: string,
     *     remediation: string,
     *     autofix_action: ?string,
     *     metadata: array<string, mixed>
     * }>
     */
    public function generate(GscInspectionDiagnosis $diagnosis, ?QuinrelMeta $meta = null, string $content = ''): array
    {
        $actions = [];

        if ($diagnosis->isBlockedByRobotsTxt()) {
            $blockingRule = $this->robotsManager?->findBlockingRule($diagnosis->url);
            $path = (string) (parse_url($diagnosis->url, PHP_URL_PATH) ?: '/');
            $ruleDetail = $blockingRule !== null
                ? "Conflicting directive found in public/robots.txt: `{$blockingRule}`."
                : 'A Disallow directive in public/robots.txt is preventing Googlebot from crawling this URL.';

            $remediation = $blockingRule !== null
                ? "Remove `{$blockingRule}` from `public/robots.txt` or add an explicit `Allow: {$path}` directive above it."
                : "Add `Allow: {$path}` to `public/robots.txt` under `User-agent: *`.";

            $actions[] = [
                'id' => 'robots_txt_blocked',
                'severity' => 'Critical',
                'priority' => 1,
                'title' => 'URL Blocked by robots.txt',
                'description' => $ruleDetail,
                'remediation' => $remediation,
                'autofix_action' => null,
                'metadata' => [
                    'conflicting_rule' => $blockingRule,
                    'suggested_directive' => "Allow: {$path}",
                ],
            ];
        }

        if ($diagnosis->isBlockedByNoindex()) {
            $source = $diagnosis->indexingState === 'BLOCKED_BY_HTTP_HEADER'
                ? 'X-Robots-Tag HTTP header'
                : 'robots meta tag';

            $actions[] = [
                'id' => 'noindex_directive',
                'severity' => 'Critical',
                'priority' => 1,
                'title' => "Indexing Blocked by {$source}",
                'description' => "Google detected a `noindex` directive via {$source} (`{$diagnosis->indexingState}`).",
                'remediation' => 'Update `quinrel_meta.robots` to `index, follow` and remove any `X-Robots-Tag: noindex` response headers.',
                'autofix_action' => 'enable_indexing',
                'metadata' => [
                    'indexing_state' => $diagnosis->indexingState,
                    'recommended_robots' => 'index, follow',
                ],
            ];
        }

        if ($diagnosis->isNotFoundOrSoft404()) {
            $existingRedirect = QuinrelRedirect::query()
                ->where('source_url', parse_url($diagnosis->url, PHP_URL_PATH) ?: $diagnosis->url)
                ->where('is_active', true)
                ->first();

            $actions[] = [
                'id' => 'soft_or_hard_404',
                'severity' => 'Critical',
                'priority' => 1,
                'title' => '404 / Soft 404 Detected',
                'description' => "Google reported `{$diagnosis->coverageState}` (`{$diagnosis->pageFetchState}`) for `{$diagnosis->url}`.",
                'remediation' => 'Establish a permanent 301 redirect via `QuinrelRedirect` to the closest valid destination URL, or return an explicit HTTP 404/410 status code if permanently removed.',
                'autofix_action' => 'create_redirect',
                'metadata' => [
                    'page_fetch_state' => $diagnosis->pageFetchState,
                    'has_active_redirect' => $existingRedirect !== null,
                ],
            ];
        }

        if ($diagnosis->hasCanonicalMismatch) {
            $metaCanonical = $meta !== null ? $meta->canonical_url : null;
            $declared = $diagnosis->userCanonical ?? $metaCanonical ?? $diagnosis->url;
            $selected = $diagnosis->googleCanonical ?? 'unspecified';

            $actions[] = [
                'id' => 'canonical_mismatch',
                'severity' => 'Warning',
                'priority' => 2,
                'title' => 'Canonical URL Mismatch',
                'description' => "Declared canonical (`{$declared}`) differs from Google-selected canonical (`{$selected}`).",
                'remediation' => 'Standardize internal links to point consistently to your preferred canonical URL, or adopt Google\'s selected canonical in `quinrel_meta.canonical_url`.',
                'autofix_action' => $diagnosis->googleCanonical !== null ? 'adopt_google_canonical' : null,
                'metadata' => [
                    'user_canonical' => $declared,
                    'google_canonical' => $diagnosis->googleCanonical,
                ],
            ];
        }

        if ($diagnosis->isCrawledNotIndexed()) {
            $metaRecord = $meta ?? QuinrelMeta::query()->where('canonical_url', $diagnosis->url)->first();
            $keyword = (string) ($metaRecord?->target_keyword ?: $metaRecord?->title ?: '');
            $metaDesc = $metaRecord !== null ? ($metaRecord->description ?? '') : '';
            $bodyToAnalyze = $content !== '' ? $content : $metaDesc;
            $inboundSuggestions = $this->linkSuggester ? $this->linkSuggester->suggest($bodyToAnalyze, $keyword) : [];
            $wordCount = str_word_count(strip_tags($bodyToAnalyze));
            $isThinContent = $content !== '' && $wordCount < 300;

            $actions[] = [
                'id' => 'crawled_not_indexed',
                'severity' => 'Warning',
                'priority' => 2,
                'title' => 'Crawled or Discovered — Currently Not Indexed',
                'description' => "Google discovered `{$diagnosis->url}` (`{$diagnosis->coverageState}`) but deferred indexing due to content depth or internal authority signals.",
                'remediation' => 'Expand thin content (aim for 300+ words of unique value), add internal contextual links from high-authority pages, verify search intent alignment, and request re-indexing.',
                'autofix_action' => 'request_indexing',
                'metadata' => [
                    'is_thin_content' => $isThinContent,
                    'word_count' => $wordCount,
                    'suggested_internal_links' => $inboundSuggestions,
                ],
            ];
        }

        if ($diagnosis->isRedirectPage()) {
            $actions[] = [
                'id' => 'page_with_redirect',
                'severity' => 'Recommendation',
                'priority' => 3,
                'title' => 'Page Redirects to Another URL',
                'description' => "Google encountered a redirect while inspecting `{$diagnosis->url}` (`{$diagnosis->coverageState}`).",
                'remediation' => 'Update XML sitemaps and internal `<a href>` links to reference the final destination URL directly so crawl budget is not wasted on intermediate hops.',
                'autofix_action' => 'flatten_redirect_chains',
                'metadata' => [
                    'google_canonical' => $diagnosis->googleCanonical,
                ],
            ];
        }

        usort($actions, fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return $actions;
    }
}
