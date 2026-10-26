<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Reporting;

use Quinrel\Laravel\Services\Accessibility\AccessibilityActionPlanService;
use Quinrel\Laravel\Services\Accessibility\AccessibilityAuditor;
use Quinrel\Laravel\Services\Analytics\PageSpeedActionPlanService;
use Quinrel\Laravel\Services\Analytics\PageSpeedInsights;
use Quinrel\Laravel\Services\Technical\SeoCrawlActionPlanService;
use Quinrel\Laravel\Services\Technical\SiteCrawlOrchestrator;
use Quinrel\Laravel\Services\Technical\TechnicalSeoAuditor;
use Quinrel\Laravel\Services\Tracking\GscIndexStatus;

class UnifiedAuditCoordinator
{
    public function __construct(
        protected ?GscIndexStatus $gscIndexStatus = null,
        protected ?SiteCrawlOrchestrator $crawlOrchestrator = null,
        protected ?SeoCrawlActionPlanService $crawlPlanService = null,
        protected ?TechnicalSeoAuditor $technicalAuditor = null,
        protected ?PageSpeedInsights $pageSpeedInsights = null,
        protected ?PageSpeedActionPlanService $pageSpeedPlanService = null,
        protected ?AccessibilityAuditor $accessibilityAuditor = null,
        protected ?AccessibilityActionPlanService $accessibilityPlanService = null,
    ) {
        $this->gscIndexStatus ??= app(GscIndexStatus::class);
        $this->crawlOrchestrator ??= app(SiteCrawlOrchestrator::class);
        $this->crawlPlanService ??= app(SeoCrawlActionPlanService::class);
        $this->technicalAuditor ??= app(TechnicalSeoAuditor::class);
        $this->pageSpeedInsights ??= app(PageSpeedInsights::class);
        $this->pageSpeedPlanService ??= app(PageSpeedActionPlanService::class);
        $this->accessibilityAuditor ??= app(AccessibilityAuditor::class);
        $this->accessibilityPlanService ??= app(AccessibilityActionPlanService::class);
    }

    /**
     * Coordinate Technical SEO, Google Indexing, PageSpeed, and Accessibility audits in a single pass.
     *
     * @return array{
     *     url: string,
     *     generated_at: string,
     *     overall_score: int,
     *     critical_count: int,
     *     warning_count: int,
     *     pillars: array{
     *         indexing: array<string, mixed>,
     *         technical_seo: array<string, mixed>,
     *         performance: array<string, mixed>,
     *         accessibility: array<string, mixed>
     *     },
     *     action_plan: array<int, array{
     *         pillar: string,
     *         severity: string,
     *         title: string,
     *         remediation: string
     *     }>
     * }
     */
    public function run(string $url, ?string $rawHtml = null): array
    {
        // 1. Google Index Status & Action Plan
        $gscResult = ($this->gscIndexStatus ?? new GscIndexStatus)->inspectAndPlan($url);
        $diagnosis = $gscResult['diagnosis'];
        $gscActions = $gscResult['actions'];

        $indexingCritical = count(array_filter($gscActions, fn (array $a): bool => $a['severity'] === 'Critical'));
        $indexingWarnings = count(array_filter($gscActions, fn (array $a): bool => $a['severity'] === 'Warning'));
        $indexingScore = match (true) {
            $indexingCritical > 0 => 25,
            $diagnosis->hasCanonicalMismatch || $indexingWarnings > 0 => 70,
            default => 100,
        };

        // 2. Technical SEO & Crawl Diagnostics
        $crawlResult = ($this->crawlOrchestrator ?? new SiteCrawlOrchestrator)->crawl(
            limit: 1,
            includeModels: false,
            chunkSize: 1,
            explicitUrls: [$url]
        );
        $crawlPlan = ($this->crawlPlanService ?? new SeoCrawlActionPlanService)->buildActionPlan($crawlResult);
        $technicalChecks = ($this->technicalAuditor ?? new TechnicalSeoAuditor)->auditUrl($url);

        $seoScore = $crawlPlan['score'];
        $seoCritical = $crawlPlan['counts']['P0'];
        $seoWarnings = $crawlPlan['counts']['P1'] + $crawlPlan['counts']['P2'];

        // 3. Lighthouse / PageSpeed Insights & Action Plan
        $psiAudit = ($this->pageSpeedInsights ?? new PageSpeedInsights)->analyze($url);
        $psiActions = $psiAudit !== null
            ? ($this->pageSpeedPlanService ?? new PageSpeedActionPlanService)->generate($psiAudit)
            : [];
        $perfScore = $psiAudit !== null ? ($psiAudit->performanceScore ?? 100) : 100;
        $perfCritical = count(array_filter($psiActions, fn (array $a): bool => $a['severity'] === 'Critical'));
        $perfWarnings = count(array_filter($psiActions, fn (array $a): bool => $a['severity'] === 'Warning'));

        // 4. Accessibility (WCAG 2.1/2.2 AA)
        $a11yRaw = $rawHtml !== null
            ? ($this->accessibilityAuditor ?? new AccessibilityAuditor)->auditHtml($rawHtml)
            : ($this->accessibilityAuditor ?? new AccessibilityAuditor)->auditUrl($url);
        $a11yPlan = ($this->accessibilityPlanService ?? new AccessibilityActionPlanService)->generate($a11yRaw);

        $a11yScore = $a11yPlan['score'];
        $a11yCritical = $a11yPlan['counts']['Critical'];
        $a11yWarnings = $a11yPlan['counts']['Serious'] + $a11yPlan['counts']['Moderate'];

        // Unified Action Plan
        $unifiedActions = [];

        foreach ($gscActions as $action) {
            $unifiedActions[] = [
                'pillar' => 'Google Indexing',
                'severity' => $action['severity'],
                'title' => $action['title'],
                'remediation' => $action['remediation'],
            ];
        }

        foreach ($crawlPlan['tickets'] as $ticket) {
            $unifiedActions[] = [
                'pillar' => 'Technical SEO',
                'severity' => $ticket['severity'],
                'title' => $ticket['title'],
                'remediation' => $ticket['remediation'],
            ];
        }

        foreach ($psiActions as $psiAction) {
            $unifiedActions[] = [
                'pillar' => 'Performance (CWV)',
                'severity' => $psiAction['severity'],
                'title' => $psiAction['title'],
                'remediation' => $psiAction['recommendation'],
            ];
        }

        foreach ($a11yPlan['tickets'] as $a11yTicket) {
            $unifiedActions[] = [
                'pillar' => 'Accessibility (a11y)',
                'severity' => $a11yTicket['severity'],
                'title' => "{$a11yTicket['rule_id']} (Line {$a11yTicket['line']}): {$a11yTicket['message']}",
                'remediation' => "Replace with: {$a11yTicket['recommended_replacement']}",
            ];
        }

        $overallScore = (int) round(
            ($seoScore * 0.30)
            + ($a11yScore * 0.30)
            + ($indexingScore * 0.20)
            + ($perfScore * 0.20)
        );

        $totalCritical = $indexingCritical + $seoCritical + $perfCritical + $a11yCritical;
        $totalWarnings = $indexingWarnings + $seoWarnings + $perfWarnings + $a11yWarnings;

        return [
            'url' => $url,
            'generated_at' => now()->toIso8601String(),
            'overall_score' => max(0, min(100, $overallScore)),
            'critical_count' => $totalCritical,
            'warning_count' => $totalWarnings,
            'pillars' => [
                'indexing' => [
                    'score' => $indexingScore,
                    'critical' => $indexingCritical,
                    'warnings' => $indexingWarnings,
                    'diagnosis' => $diagnosis->toArray(),
                    'actions' => $gscActions,
                ],
                'technical_seo' => [
                    'score' => $seoScore,
                    'critical' => $seoCritical,
                    'warnings' => $seoWarnings,
                    'checks' => $technicalChecks,
                    'counts' => $crawlPlan['counts'],
                    'tickets' => $crawlPlan['tickets'],
                ],
                'performance' => [
                    'score' => $perfScore,
                    'critical' => $perfCritical,
                    'warnings' => $perfWarnings,
                    'audit' => $psiAudit?->toArray(),
                    'actions' => $psiActions,
                ],
                'accessibility' => [
                    'score' => $a11yScore,
                    'critical' => $a11yCritical,
                    'warnings' => $a11yWarnings,
                    'counts' => $a11yPlan['counts'],
                    'tickets' => $a11yPlan['tickets'],
                ],
            ],
            'action_plan' => $unifiedActions,
        ];
    }
}
