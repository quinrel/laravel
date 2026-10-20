<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Models\QuinrelRumMetric;
use Quinrel\Laravel\Services\Analytics\Ga4Client;
use Quinrel\Laravel\Services\Analytics\PageSpeedInsights;
use Quinrel\Laravel\Services\Reporting\ClientReportGenerator;
use Quinrel\Laravel\Services\Schema\ImageSchemaEnricher;
use Quinrel\Laravel\Services\Technical\BrokenLinkChecker;
use Quinrel\Laravel\Services\Technical\TechnicalSeoAuditor;
use Quinrel\Laravel\Support\UrlValidator;
use Quinrel\Laravel\Tests\TestCase;

class SecurityAndComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_unauthenticated_access_to_dashboard_and_analyze_api_while_keeping_llms_txt_public(): void
    {
        $this->get('/quinrel')->assertForbidden();
        $this->postJson('/quinrel/api/analyze', ['title' => 'Test'])->assertForbidden();

        // Root /llms.txt and /llms-full.txt remain publicly accessible
        $this->get('/llms.txt')->assertOk();
        $this->get('/llms-full.txt')->assertOk();

        // Authorized user with 'admin' role can access dashboard & analyze endpoint
        $admin = new User;
        $admin->id = 1;
        $admin->setAttribute('role', 'admin');

        $this->actingAs($admin)
            ->postJson('/quinrel/api/analyze', [
                'title' => 'Laravel SEO Guide',
                'description' => 'Learn how to optimize your Laravel application for search engines.',
                'content' => '<p>Laravel SEO Guide content with focus keyword.</p>',
                'focus_keyword' => 'Laravel SEO',
            ])
            ->assertOk()
            ->assertJsonStructure(['seo', 'readability', 'internal_links']);
    }

    public function test_ssrf_protection_blocks_private_loopback_metadata_and_non_http_urls(): void
    {
        Http::fake();

        $unsafeUrls = [
            'http://169.254.169.254/latest/meta-data/',
            'http://127.0.0.1:6379/info',
            'http://localhost:8080/admin',
            'http://10.0.0.1/internal',
            'http://192.168.1.1/router',
            'phar://malicious.phar/payload',
            'file:///etc/passwd',
        ];

        $enricher = new ImageSchemaEnricher;
        $auditor = new TechnicalSeoAuditor;
        $checker = new BrokenLinkChecker;

        foreach ($unsafeUrls as $unsafeUrl) {
            $this->assertFalse(UrlValidator::isSafe($unsafeUrl), "Expected {$unsafeUrl} to be blocked.");

            $schema = $enricher->enrich($unsafeUrl);
            $this->assertArrayNotHasKey('width', $schema);

            $audit = $auditor->auditUrl($unsafeUrl);
            $this->assertSame('error', $audit['https']['status']);
        }

        $linkResults = $checker->check($unsafeUrls);
        foreach ($unsafeUrls as $unsafeUrl) {
            $this->assertTrue($linkResults[$unsafeUrl]['is_broken']);
            $this->assertIsString($linkResults[$unsafeUrl]['error']);
        }

        Http::assertNothingSent();
    }

    public function test_escapes_xss_in_client_report_generator_html(): void
    {
        $ga4 = $this->createMock(Ga4Client::class);
        $ga4->method('getSessionTrends')->willReturn([]);
        $ga4->method('getTopLandingPages')->willReturn([]);

        $psi = $this->createMock(PageSpeedInsights::class);
        $psi->method('fetch')->willReturn(['score' => 95]);

        $generator = new ClientReportGenerator($ga4, $psi);
        $html = $generator->generateHtml('https://example.com/?q=<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_cookie_notice_and_rum_directive_are_gdpr_and_csp_compliant(): void
    {
        $cookieHtml = Blade::render('<x-quinrel-cookie-notice privacy-url="/custom-privacy" />');

        $this->assertStringContainsString('/custom-privacy', $cookieHtml);
        $this->assertStringContainsString('acceptQuinrelCookies()', $cookieHtml);
        $this->assertStringContainsString('rejectQuinrelCookies()', $cookieHtml);

        $rumHtml = Blade::render('@quinrelRum');
        $this->assertStringNotContainsString('unpkg.com', $rumHtml);
        $this->assertStringContainsString('PerformanceObserver', $rumHtml);
        $this->assertStringContainsString('/quinrel/api/v1/rum', $rumHtml);
    }

    public function test_rum_controller_accepts_both_single_and_batched_payloads(): void
    {
        // Single payload
        $this->postJson('/quinrel/api/v1/rum', [
            'url' => 'https://example.com/blog?utm_source=test',
            'metric_name' => 'LCP',
            'metric_value' => 1850.5,
            'rating' => 'good',
            'session_id' => 'sess-1',
        ])->assertStatus(202);

        // Batched payload
        $this->postJson('/quinrel/api/v1/rum', [
            'url' => 'https://example.com/pricing?ref=nav',
            'session_id' => 'sess-2',
            'metrics' => [
                ['metric_name' => 'LCP', 'metric_value' => 2100, 'rating' => 'good'],
                ['metric_name' => 'CLS', 'metric_value' => 0.04, 'rating' => 'good'],
                ['metric_name' => 'INP', 'metric_value' => 120, 'rating' => 'good'],
            ],
        ])->assertStatus(202);

        $this->assertSame(4, QuinrelRumMetric::count());
        $this->assertDatabaseHas('quinrel_rum_metrics', [
            'url' => 'https://example.com/pricing',
            'metric_name' => 'CLS',
            'session_id' => 'sess-2',
        ]);
    }
}
