<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Accessibility\AccessibilityActionPlanService;
use Quinrel\Laravel\Services\Accessibility\AccessibilityAuditor;
use Quinrel\Laravel\Services\Media\AiAltTextGenerator;
use Quinrel\Laravel\Tests\TestCase;

class Epic4AccessibilityAuditAndRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_audits_wcag_aa_rules_and_generates_code_replacement_tickets(): void
    {
        $failingHtml = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title>Inaccessible Page</title></head>
<body>
    <h2>Skipped H1 directly to H2</h2>
    <h4>Skipped H2 directly to H4</h4>
    <img src="/images/team-retreat.jpg">
    <img src="/images/DSC_0001.jpg" alt="DSC_0001">
    <img src="/images/banner.png" alt="">
    <form>
        <input type="email" name="email">
        <select name="country"><option>US</option></select>
    </form>
    <button><svg viewBox="0 0 24 24"><path d="M0 0h24v24H0z"/></svg></button>
    <a href="/pricing"><svg><use href="#arrow"/></svg></a>
    <div id="dup-section">First</div>
    <div id="dup-section">Second</div>
</body>
</html>
HTML;

        $auditor = app(AccessibilityAuditor::class);
        $audit = $auditor->auditHtml($failingHtml);

        $this->assertLessThan(50, $audit['score']);
        $this->assertGreaterThan(0, $audit['counts']['Critical']);
        $this->assertGreaterThan(0, $audit['counts']['Serious']);
        $this->assertGreaterThan(0, $audit['counts']['Moderate']);

        $ruleIds = array_column($audit['violations'], 'rule_id');
        $this->assertContains('html-has-lang', $ruleIds);
        $this->assertContains('heading-missing-h1', $ruleIds);
        $this->assertContains('heading-order-skipped', $ruleIds);
        $this->assertContains('img-alt-missing', $ruleIds);
        $this->assertContains('img-alt-placeholder', $ruleIds);
        $this->assertContains('img-alt-empty', $ruleIds);
        $this->assertContains('form-label', $ruleIds);
        $this->assertContains('interactive-accessible-name', $ruleIds);
        $this->assertContains('duplicate-id', $ruleIds);

        $planService = app(AccessibilityActionPlanService::class);
        $plan = $planService->generate($audit);

        $this->assertNotEmpty($plan['grouped']['Critical']);
        $this->assertNotEmpty($plan['grouped']['Serious']);
        $this->assertNotEmpty($plan['grouped']['Moderate']);

        $replacements = implode("\n", array_column($plan['tickets'], 'recommended_replacement'));
        $this->assertStringContainsString('aria-label="Toggle action"', $replacements);
        $this->assertStringContainsString('aria-label="Navigate to Pricing"', $replacements);
        $this->assertStringContainsString('<label for="email">Email</label>', $replacements);
        $this->assertStringContainsString('<html lang="en">', $replacements);
        $this->assertStringContainsString('id="dup-section-2"', $replacements);
    }

    public function test_passes_compliant_wcag_aa_html_with_perfect_score(): void
    {
        $compliantHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en-US">
<head><title>Accessible Document</title></head>
<body>
    <h1>Main Page Heading</h1>
    <h2>Section Heading</h2>
    <img src="/images/architecture.webp" alt="System architecture diagram showing request lifecycle">
    <img src="/images/divider.svg" alt="" role="presentation">
    <form>
        <label for="search-input">Search Documentation</label>
        <input id="search-input" type="search" name="q">
    </form>
    <button type="button" aria-label="Close dialog"><svg></svg></button>
</body>
</html>
HTML;

        $audit = app(AccessibilityAuditor::class)->auditHtml($compliantHtml);

        $this->assertSame(100, $audit['score']);
        $this->assertEmpty($audit['violations']);
    }

    public function test_ai_alt_text_generator_and_artisan_commands(): void
    {
        Schema::create('dummy_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->timestamps();
        });

        config([
            'quinrel.ai.provider' => 'openai',
            'quinrel.ai.openai_key' => 'sk-test-a11y',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'alt_text' => 'Dashboard chart illustrating organic search growth over 30 days',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $article = DummyArticle::create([
            'title' => 'Q4 SEO Growth Report',
            'body' => '<p>Here is our performance chart:</p><img src="/charts/q4-growth.png"><img src="/charts/DSC_0009.jpg" alt="DSC_0009">',
        ]);

        $meta = QuinrelMeta::create([
            'model_type' => DummyArticle::class,
            'model_id' => $article->id,
            'title' => 'Q4 SEO Growth Report',
            'description' => 'Detailed organic metrics.',
            'og_image' => 'https://example.com/images/og-q4.png',
        ]);

        $generator = app(AiAltTextGenerator::class);
        $fixedCount = $generator->autofixModel($article);

        $this->assertSame(2, $fixedCount);
        $article->refresh();
        $this->assertStringContainsString(
            'alt="Dashboard chart illustrating organic search growth over 30 days"',
            (string) $article->body
        );

        $this->artisan('quinrel:autofix-alt-text --model=DummyArticle')
            ->assertExitCode(0);

        $meta->refresh();
        $this->assertSame(
            'Dashboard chart illustrating organic search growth over 30 days',
            $meta->social_overrides['og_image_alt'] ?? null
        );

        $this->artisan('quinrel:audit-a11y', [
            '--html' => '<!DOCTYPE html><html lang="en"><body><h1>Valid Page</h1></body></html>',
        ])->assertExitCode(0);
    }
}
