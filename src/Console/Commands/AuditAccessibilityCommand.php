<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Services\Accessibility\AccessibilityActionPlanService;
use Quinrel\Laravel\Services\Accessibility\AccessibilityAuditor;

class AuditAccessibilityCommand extends Command
{
    protected $signature = 'quinrel:audit-a11y
                            {url? : The URL to audit (defaults to app.url)}
                            {--html= : Direct HTML snippet to audit instead of fetching a URL}';

    protected $description = 'Run a WCAG 2.1/2.2 AA static accessibility audit and generate remediation snippets';

    public function handle(
        AccessibilityAuditor $auditor,
        AccessibilityActionPlanService $planService
    ): int {
        $htmlOption = $this->option('html');
        $urlArg = $this->argument('url');
        $targetUrl = is_string($urlArg) && trim($urlArg) !== ''
            ? trim($urlArg)
            : (string) config('app.url', 'https://example.com');

        if (is_string($htmlOption) && trim($htmlOption) !== '') {
            $audit = $auditor->auditHtml($htmlOption);
        } else {
            $this->info("Auditing accessibility for {$targetUrl}...");
            $audit = $auditor->auditUrl($targetUrl);
        }

        $plan = $planService->generate($audit);

        Cache::put('quinrel.a11y.latest', [
            'audited_at' => now()->toIso8601String(),
            'url' => $targetUrl,
            'plan' => $plan,
        ], now()->addDays(7));

        $this->info("Accessibility Compliance Score: {$plan['score']}/100");
        $this->table(
            ['Severity', 'Count'],
            [
                ['Critical', $plan['counts']['Critical']],
                ['Serious', $plan['counts']['Serious']],
                ['Moderate', $plan['counts']['Moderate']],
            ]
        );

        if ($plan['tickets'] !== []) {
            $rows = array_map(
                fn (array $t): array => [
                    $t['severity'],
                    $t['rule_id'].' ('.$t['wcag'].')',
                    (string) $t['line'],
                    $t['failing_snippet'],
                    $t['recommended_replacement'],
                ],
                $plan['tickets']
            );
            $this->table(['Severity', 'Rule', 'Line', 'Failing HTML', 'Recommended Replacement'], $rows);
        } else {
            $this->info('Zero WCAG violations detected.');
        }

        return self::SUCCESS;
    }
}
