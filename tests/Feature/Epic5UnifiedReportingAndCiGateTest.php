<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Services\Reporting\ClientReportGenerator;
use Quinrel\Laravel\Services\Reporting\UnifiedAuditCoordinator;
use Quinrel\Laravel\Tests\TestCase;

class Epic5UnifiedReportingAndCiGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_audit_coordinator_and_ci_quality_gate_command(): void
    {
        $healthyHtml = '<!DOCTYPE html><html lang="en"><head>'
            .'<title>Enterprise Laravel SEO Platform &amp; Analytics</title>'
            .'<meta name="description" content="Complete enterprise SEO, structured data, and real-user monitoring suite built natively for Laravel applications.">'
            .'<link rel="canonical" href="https://example.com/enterprise">'
            .'<link rel="icon" href="/favicon.ico">'
            .'<link rel="apple-touch-icon" href="/apple-touch-icon.png">'
            .'</head><body><h1>Enterprise Laravel SEO</h1><p>'
            .str_repeat('High quality authoritative technical documentation for Laravel engineers. ', 30)
            .'</p></body></html>';

        Http::fake([
            'https://example.com/enterprise' => Http::response(
                $healthyHtml,
                200,
                ['Strict-Transport-Security' => 'max-age=31536000']
            ),
            'https://example.com/broken' => Http::response(
                '<html><body><img src="/unlabeled.png"></body></html>',
                404
            ),
            'www.googleapis.com/pagespeedonline/v5/runPagespeed*' => Http::response([
                'lighthouseResult' => [
                    'categories' => [
                        'performance' => ['score' => 0.96],
                        'seo' => ['score' => 0.98],
                        'best-practices' => ['score' => 0.95],
                    ],
                    'audits' => [],
                ],
            ], 200),
        ]);

        $coordinator = app(UnifiedAuditCoordinator::class);
        $passingReport = $coordinator->run('https://example.com/enterprise');

        $this->assertGreaterThanOrEqual(85, $passingReport['overall_score']);
        $this->assertSame(0, $passingReport['critical_count']);

        $this->artisan('quinrel:audit-all https://example.com/enterprise')
            ->assertExitCode(0);

        $this->artisan('quinrel:ci-check https://example.com/enterprise --max-critical=0 --min-score=85')
            ->assertExitCode(0);

        // Failing URL should exit with code 1 (FAILURE)
        $this->artisan('quinrel:ci-check https://example.com/broken --max-critical=0 --min-score=85')
            ->assertExitCode(1);
    }

    public function test_executive_client_report_generator_outputs_html_csv_and_json(): void
    {
        Http::fake([
            'https://example.com/report-target' => Http::response(
                '<!DOCTYPE html><html lang="en"><head><title>Report Target Page</title>'
                .'<meta name="description" content="Valid description with more than fifty characters for the executive report test.">'
                .'<link rel="icon" href="/favicon.ico"><link rel="apple-touch-icon" href="/apple-touch-icon.png">'
                .'</head><body><h1>Report Target</h1><p>'.str_repeat('Executive report body text ', 35).'</p></body></html>',
                200,
                ['Strict-Transport-Security' => 'max-age=31536000']
            ),
            'www.googleapis.com/pagespeedonline/v5/runPagespeed*' => Http::response([
                'lighthouseResult' => [
                    'categories' => [
                        'performance' => ['score' => 0.94],
                    ],
                ],
            ], 200),
        ]);

        $generator = app(ClientReportGenerator::class);

        $html = $generator->generateHtml('https://example.com/report-target');
        $this->assertStringContainsString('Quinrel Executive SEO, Performance &amp; Accessibility Report', $html);
        $this->assertStringContainsString('https://example.com/report-target', $html);

        $csv = $generator->generateCsv('https://example.com/report-target');
        $this->assertStringContainsString('Overall Score', $csv);
        $this->assertStringContainsString('Pillar: Technical seo', $csv);

        $json = $generator->generateJson('https://example.com/report-target');
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('pillars', $decoded);
        $this->assertArrayHasKey('action_plan', $decoded);
    }
}
