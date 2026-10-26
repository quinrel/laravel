<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Quinrel\Laravel\Services\Reporting\UnifiedAuditCoordinator;

class AuditAllCommand extends Command
{
    protected $signature = 'quinrel:audit-all
                            {url? : Target URL to audit (defaults to app.url)}
                            {--json : Output unified audit report as JSON}';

    protected $description = 'Execute Technical SEO, Google Indexing, PageSpeed, and Accessibility audits in a single pass';

    public function handle(UnifiedAuditCoordinator $coordinator): int
    {
        $urlArg = $this->argument('url');
        $url = is_string($urlArg) && trim($urlArg) !== ''
            ? trim($urlArg)
            : (string) config('app.url', 'https://example.com');

        $report = $coordinator->run($url);

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info("Quinrel Unified Audit Report for: {$url}");
        $this->info("Overall Composite Score: {$report['overall_score']}/100 | Critical Issues: {$report['critical_count']} | Warnings: {$report['warning_count']}");

        $pillars = $report['pillars'];
        $this->table(
            ['Pillar', 'Score', 'Critical', 'Warnings'],
            [
                ['Google Indexing', $pillars['indexing']['score'].'/100', $pillars['indexing']['critical'], $pillars['indexing']['warnings']],
                ['Technical SEO', $pillars['technical_seo']['score'].'/100', $pillars['technical_seo']['critical'], $pillars['technical_seo']['warnings']],
                ['Performance (CWV)', $pillars['performance']['score'].'/100', $pillars['performance']['critical'], $pillars['performance']['warnings']],
                ['Accessibility (a11y)', $pillars['accessibility']['score'].'/100', $pillars['accessibility']['critical'], $pillars['accessibility']['warnings']],
            ]
        );

        if ($report['action_plan'] !== []) {
            $rows = array_map(
                fn (array $item): array => [
                    $item['pillar'],
                    $item['severity'],
                    $item['title'],
                    $item['remediation'],
                ],
                array_slice($report['action_plan'], 0, 25)
            );
            $this->table(['Pillar', 'Severity', 'Finding', 'Remediation'], $rows);
        } else {
            $this->info('All four diagnostic pillars passed with zero action items.');
        }

        return self::SUCCESS;
    }
}
