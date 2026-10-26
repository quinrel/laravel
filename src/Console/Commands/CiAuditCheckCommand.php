<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Quinrel\Laravel\Services\Reporting\UnifiedAuditCoordinator;

class CiAuditCheckCommand extends Command
{
    protected $signature = 'quinrel:ci-check
                            {url? : Target URL to audit (defaults to app.url)}
                            {--max-critical=0 : Maximum allowed critical errors before failing the build}
                            {--min-score=85 : Minimum required composite score (0-100)}
                            {--html= : Optional HTML snippet for static CI evaluation}';

    protected $description = 'Run CI/CD quality gates across SEO, Indexing, Performance, and Accessibility';

    public function handle(UnifiedAuditCoordinator $coordinator): int
    {
        $urlArg = $this->argument('url');
        $url = is_string($urlArg) && trim($urlArg) !== ''
            ? trim($urlArg)
            : (string) config('app.url', 'https://example.com');

        $maxCritical = max(0, (int) $this->option('max-critical'));
        $minScore = max(0, min(100, (int) $this->option('min-score')));
        $rawHtml = is_string($this->option('html')) && trim((string) $this->option('html')) !== ''
            ? (string) $this->option('html')
            : null;

        $report = $coordinator->run($url, $rawHtml);

        $score = $report['overall_score'];
        $criticalCount = $report['critical_count'];

        $this->info("CI Quality Gate Check for {$url}: Score={$score}/100 (min: {$minScore}), Critical={$criticalCount} (max: {$maxCritical})");

        if ($criticalCount > $maxCritical) {
            $this->error("CI Quality Gate FAILED: Critical issue count ({$criticalCount}) exceeds maximum allowed ({$maxCritical}).");

            return self::FAILURE;
        }

        if ($score < $minScore) {
            $this->error("CI Quality Gate FAILED: Composite score ({$score}) is below minimum threshold ({$minScore}).");

            return self::FAILURE;
        }

        $this->info('CI Quality Gate PASSED.');

        return self::SUCCESS;
    }
}
