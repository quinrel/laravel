<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Middleware\Quinrel404MonitorMiddleware;
use Quinrel\Laravel\Models\Quinrel404Log;
use Quinrel\Laravel\Models\QuinrelAuditLog;
use Quinrel\Laravel\Models\QuinrelIndexingLog;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Models\QuinrelRumMetric;
use Quinrel\Laravel\Services\Analytics\Ga4Client;
use Quinrel\Laravel\Services\Analytics\PageSpeedInsights;
use Quinrel\Laravel\Services\HeadRenderer;
use Quinrel\Laravel\Services\Reporting\ClientReportGenerator;
use Quinrel\Laravel\Services\Schema\ImageSchemaEnricher;
use Quinrel\Laravel\Services\Schema\SchemaBuilder;
use Quinrel\Laravel\Services\Schema\SchemaGenerator;
use Quinrel\Laravel\Services\Technical\BrokenLinkChecker;
use Quinrel\Laravel\Services\Technical\TechnicalSeoAuditor;
use Quinrel\Laravel\Support\UrlValidator;
use Quinrel\Laravel\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

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

    public function test_json_ld_schema_escapes_script_breakout_tags(): void
    {
        $payload = '</script><script>alert("xss")</script>';

        $builderHtml = SchemaBuilder::make('Article')
            ->set('headline', $payload)
            ->toHtml();

        $this->assertStringNotContainsString('</script><script>', $builderHtml);
        $this->assertStringContainsString('\u003C/script\u003E', $builderHtml);

        $generator = new SchemaGenerator;
        $generator->addCustom(['@type' => 'WebPage', 'name' => $payload]);
        $rendered = $generator->render();

        $this->assertStringNotContainsString('</script><script>', $rendered);
        $this->assertStringContainsString('\u003C/script\u003E', $rendered);
    }

    public function test_discovery_scan_and_noindex_headless_seo_enforce_authorization(): void
    {
        $this->getJson('/quinrel/api/v1/discovery/scan?url=https://example.com')
            ->assertForbidden();

        config(['quinrel.api.cloud_key' => 'secret-cloud-token']);
        Http::fake([
            'https://example.com' => Http::response('<!DOCTYPE html><html lang="en"><head><link rel="icon" href="/favicon.ico"></head><body></body></html>', 200),
        ]);

        $this->withHeader('X-Quinrel-Token', 'secret-cloud-token')
            ->getJson('/quinrel/api/v1/discovery/scan?url=https://example.com')
            ->assertOk();

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 777,
            'title' => 'Private Draft Page',
            'robots' => 'noindex, nofollow',
        ]);

        // Unauthenticated caller cannot enumerate noindex metadata
        $this->getJson('/quinrel/api/v1/seo/777')->assertNotFound();

        // Authorized user can view noindex metadata
        $editor = new User;
        $editor->id = 2;
        $editor->setAttribute('role', 'editor');

        $this->actingAs($editor)
            ->getJson('/quinrel/api/v1/seo/777')
            ->assertOk()
            ->assertJsonPath('title', 'Private Draft Page');
    }

    public function test_ssrf_safe_get_blocks_malicious_redirect_hops_and_ipv4_mapped_ipv6(): void
    {
        $this->assertFalse(UrlValidator::isSafe('http://[::ffff:127.0.0.1]/admin'));
        $this->assertFalse(UrlValidator::isSafe('http://[::ffff:169.254.169.254]/latest/meta-data/'));

        Http::fake([
            'https://example.com/open-redirect' => Http::response('', 302, [
                'Location' => 'http://169.254.169.254/latest/meta-data/',
            ]),
        ]);

        $this->assertNull(UrlValidator::safeGet('https://example.com/open-redirect'));

        $auditor = new TechnicalSeoAuditor;
        $audit = $auditor->auditUrl('https://example.com/open-redirect');
        $this->assertSame('error', $audit['https']['status']);
        $this->assertStringContainsString('SSRF', $audit['https']['message']);
    }

    public function test_setup_wizard_requires_manage_quinrel_and_wires_runtime_fallbacks(): void
    {
        config(['quinrel.social.twitter' => '']);

        $author = new User;
        $author->id = 10;
        $author->setAttribute('role', 'author');

        $this->actingAs($author)
            ->postJson('/quinrel/api/setup', [
                'siteName' => 'Corporate',
                'twitterHandle' => '@unauthorized',
                'aiProvider' => 'openai',
            ])
            ->assertForbidden();

        $admin = new User;
        $admin->id = 1;
        $admin->setAttribute('role', 'admin');

        $this->actingAs($admin)
            ->postJson('/quinrel/api/setup', [
                'siteName' => 'Corporate',
                'twitterHandle' => '@configured_handle',
                'aiProvider' => 'anthropic',
            ])
            ->assertOk();

        $meta = app(HeadRenderer::class)->resolveMetadata();
        $this->assertSame('@configured_handle', $meta['twitter_handle']);
    }

    public function test_404_monitor_rate_limiting_and_log_models_prunable(): void
    {
        config(['quinrel.redirects.max_404_logs_per_minute' => 2]);

        $middleware = new Quinrel404MonitorMiddleware;

        for ($i = 1; $i <= 4; $i++) {
            $req = Request::create("/probe-{$i}", 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.55']);
            $middleware->handle($req, fn () => new Response('Not Found', 404));
        }

        // Only the first 2 requests within the rate limit window are persisted
        $this->assertSame(2, Quinrel404Log::count());

        $this->assertInstanceOf(Builder::class, (new Quinrel404Log)->prunable());
        $this->assertInstanceOf(Builder::class, (new QuinrelAuditLog)->prunable());
        $this->assertInstanceOf(Builder::class, (new QuinrelIndexingLog)->prunable());
    }
}
