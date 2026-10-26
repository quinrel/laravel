<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Reporting;

use Illuminate\Support\Facades\View;
use Quinrel\Laravel\Services\Analytics\Ga4Client;
use Quinrel\Laravel\Services\Analytics\PageSpeedInsights;

class ClientReportGenerator
{
    public function __construct(
        protected Ga4Client $ga4Client,
        protected PageSpeedInsights $pageSpeed,
        protected ?UnifiedAuditCoordinator $auditCoordinator = null,
    ) {}

    /**
     * Generate comprehensive executive report data incorporating all 4 diagnostic pillars and GA4 analytics.
     *
     * @return array<string, mixed>
     */
    public function generateData(string $url): array
    {
        $pagespeed = $this->pageSpeed->fetch($url);
        $unified = $this->auditCoordinator?->run($url);

        return [
            'generated_at' => now()->toIso8601String(),
            'url' => $url,
            'overall_score' => $unified['overall_score'] ?? ($pagespeed['score'] ?? 100),
            'critical_count' => $unified['critical_count'] ?? 0,
            'warning_count' => $unified['warning_count'] ?? 0,
            'pagespeed' => $pagespeed,
            'analytics' => [
                'sessions' => $this->ga4Client->getSessionTrends(),
                'top_pages' => $this->ga4Client->getTopLandingPages(),
            ],
            'pillars' => $unified['pillars'] ?? [],
            'action_plan' => $unified['action_plan'] ?? [],
        ];
    }

    public function generateJson(string $url): string
    {
        $data = $this->generateData($url);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function generateHtml(string $url): string
    {
        $data = $this->generateData($url);

        if (View::exists('quinrel::reports.executive')) {
            /** @var view-string $executiveView */
            $executiveView = 'quinrel::reports.executive';

            return View::make($executiveView, ['report' => $data])->render();
        }

        if (View::exists('quinrel::reports.client')) {
            /** @var view-string $clientView */
            $clientView = 'quinrel::reports.client';

            return View::make($clientView, ['report' => $data])->render();
        }

        $safeUrl = e($url);
        $safeJson = e((string) json_encode($data, JSON_PRETTY_PRINT));

        return "<html><body><h1>SEO Report for {$safeUrl}</h1><pre>{$safeJson}</pre></body></html>";
    }

    public function generateCsv(string $url): string
    {
        $data = $this->generateData($url);
        $output = fopen('php://temp', 'r+');
        if (! $output) {
            return '';
        }

        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Generated At', (string) $data['generated_at']]);
        fputcsv($output, ['URL', (string) $data['url']]);
        fputcsv($output, ['Overall Score', (string) ($data['overall_score'] ?? 100)]);
        fputcsv($output, ['Critical Issues', (string) ($data['critical_count'] ?? 0)]);
        fputcsv($output, ['Warnings', (string) ($data['warning_count'] ?? 0)]);

        $pagespeed = is_array($data['pagespeed'] ?? null) ? $data['pagespeed'] : [];
        if (isset($pagespeed['score'])) {
            fputcsv($output, ['PageSpeed Score', (string) $pagespeed['score']]);
        }

        $pillars = is_array($data['pillars'] ?? null) ? $data['pillars'] : [];
        foreach ($pillars as $name => $pillar) {
            if (is_array($pillar) && isset($pillar['score'])) {
                fputcsv($output, ['Pillar: '.ucfirst(str_replace('_', ' ', (string) $name)), (string) $pillar['score']]);
            }
        }

        $actionPlan = is_array($data['action_plan'] ?? null) ? $data['action_plan'] : [];
        if ($actionPlan !== []) {
            fputcsv($output, []);
            fputcsv($output, ['Pillar', 'Severity', 'Finding', 'Remediation']);
            foreach ($actionPlan as $item) {
                if (is_array($item)) {
                    fputcsv($output, [
                        (string) ($item['pillar'] ?? ''),
                        (string) ($item['severity'] ?? ''),
                        (string) ($item['title'] ?? ''),
                        (string) ($item['remediation'] ?? ''),
                    ]);
                }
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv !== false ? $csv : '';
    }
}
