<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Quinrel\Laravel\Facades\Quinrel;
use Quinrel\Laravel\Middleware\AeoContentNegotiationMiddleware;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Accessibility\AccessibilityAuditor;
use Quinrel\Laravel\Services\Analysis\ContentAnalyzer;
use Quinrel\Laravel\Services\Analysis\SeoChecklist;
use Quinrel\Laravel\Services\RobotsTxtManager;
use Quinrel\Laravel\Services\Technical\TechnicalSeoAuditor;
use Quinrel\Laravel\Tests\TestCase;

class GeoToolkitTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_manager_audits_and_applies_geo_crawler_policies(): void
    {
        $tempRobotsPath = sys_get_temp_dir().'/quinrel_test_robots_'.uniqid().'.txt';
        File::put($tempRobotsPath, "User-agent: *\nAllow: /\nDisallow: /admin\n");

        try {
            $manager = new RobotsTxtManager($tempRobotsPath);

            // Initially all AI search and training bots inherit User-agent: * (allowed on /)
            $initialAudit = $manager->auditAiBots('/');
            $this->assertTrue($initialAudit['all_search_bots_allowed']);
            $this->assertFalse($initialAudit['training_bots_blocked']);
            $this->assertFalse($initialAudit['sitemap_referenced']);

            // Apply GEO policy: allow AI search/citation bots, block AI training scrapers
            $this->assertTrue($manager->applyGeoPolicy(
                allowSearchBots: true,
                blockTrainingBots: true,
                sitemapUrl: 'https://example.com/sitemap.xml'
            ));

            $updatedContent = $manager->get();
            $this->assertStringContainsString("User-agent: *\nAllow: /\nDisallow: /admin", str_replace("\r\n", "\n", $updatedContent));
            $this->assertStringContainsString('# BEGIN Quinrel GEO', $updatedContent);
            $this->assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $updatedContent);

            $auditAfter = $manager->auditAiBots('/');
            $this->assertTrue($auditAfter['all_search_bots_allowed']);
            $this->assertTrue($auditAfter['training_bots_blocked']);
            $this->assertTrue($auditAfter['sitemap_referenced']);
            $this->assertTrue($manager->isBotAllowed('OAI-SearchBot', '/articles/laravel-geo'));
            $this->assertTrue($manager->isBotAllowed('PerplexityBot', '/articles/laravel-geo'));
            $this->assertFalse($manager->isBotAllowed('GPTBot', '/articles/laravel-geo'));
            $this->assertFalse($manager->isBotAllowed('CCBot', '/articles/laravel-geo'));

            // Applying policy a second time updates the managed block idempotently without duplicating it
            $manager->applyGeoPolicy(allowSearchBots: true, blockTrainingBots: false, sitemapUrl: 'https://example.com/sitemap.xml');
            $this->assertSame(1, substr_count($manager->get(), '# BEGIN Quinrel GEO'));
            $this->assertTrue($manager->isBotAllowed('GPTBot', '/'));
        } finally {
            if (File::exists($tempRobotsPath)) {
                File::delete($tempRobotsPath);
            }
        }
    }

    public function test_aeo_middleware_alias_and_markdown_alternate_head_link(): void
    {
        $this->assertSame(AeoContentNegotiationMiddleware::class, Quinrel::aeo());

        Route::get('/geo-guide', function () {
            Quinrel::setTitle('Laravel GEO Guide')
                ->setDescription('How to optimize for AI search engines.')
                ->setCanonical('https://example.com/geo-guide')
                ->setRobots('index, follow');

            $head = Blade::render('@quinrelHead');

            return response("<!DOCTYPE html><html><head>{$head}</head><body><nav>Menu</nav><main><h1>Laravel GEO Guide</h1><p>Direct answer paragraph.</p></main><footer>Footer</footer></body></html>");
        })->middleware('quinrel.aeo');

        // 1. Standard HTML request advertises <link rel="alternate" type="text/markdown"> in <head>
        config(['quinrel.defaults.noindex_non_production' => false]);
        $htmlResponse = $this->get('/geo-guide');
        $htmlResponse->assertOk();
        $htmlResponse->assertSee('<link rel="alternate" type="text/markdown" href="https://example.com/geo-guide?format=md">', false);

        // 2. Request with ?format=md or Accept: text/markdown negotiates clean Markdown excluding <nav> and <footer>
        $mdResponse = $this->get('/geo-guide?format=md');
        $mdResponse->assertOk();
        $mdResponse->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $mdResponse->assertHeader('Vary', 'Accept');
        $this->assertStringContainsString('# Laravel GEO Guide', (string) $mdResponse->getContent());
        $this->assertStringContainsString('Direct answer paragraph.', (string) $mdResponse->getContent());
        $this->assertStringNotContainsString('Menu', (string) $mdResponse->getContent());
        $this->assertStringNotContainsString('Footer', (string) $mdResponse->getContent());
    }

    public function test_llms_txt_uses_structured_config_description_and_advertises_markdown_negotiation(): void
    {
        Cache::flush();

        config([
            'app.name' => 'Acme AI Platform',
            'quinrel.llms_txt.description' => 'Official developer documentation and API references for Acme AI.',
        ]);

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 1,
            'title' => 'Getting Started with Acme',
            'description' => 'Quickstart guide for developers.',
            'canonical_url' => 'https://example.com/docs/getting-started',
            'robots' => 'index, follow',
        ]);

        $indexResponse = $this->get('/llms.txt');
        $indexResponse->assertOk();
        $indexResponse->assertSee('> Official developer documentation and API references for Acme AI.', false);
        $indexResponse->assertSee('- [Getting Started with Acme](https://example.com/docs/getting-started) - Quickstart guide for developers.', false);

        $fullResponse = $this->get('/llms-full.txt');
        $fullResponse->assertOk();
        $fullResponse->assertSee('## Markdown Content Negotiation', false);
        $fullResponse->assertSee('?format=md', false);
    }

    public function test_seo_checklist_and_content_analyzer_evaluate_geo_citability(): void
    {
        $richGeoHtml = <<<'HTML'
        <h1>Generative Engine Optimization in Laravel</h1>
        <h2>What is Generative Engine Optimization (GEO)?</h2>
        <p>Generative Engine Optimization is the practice of structuring web content, entity schemas, and machine-readable endpoints so AI answer engines like Perplexity, ChatGPT Search, and Google AI Overviews accurately cite your pages.</p>
        <ul>
            <li>Provide concise 25 to 80 word answer capsules immediately after question headings.</li>
            <li>Expose clean Markdown via content negotiation and root llms.txt indexes.</li>
        </ul>
        <p>Empirical studies show that adding quantitative benchmarks—such as a 37% increase in citation recall and 120ms faster parsing—alongside authoritative references like <a href="https://schema.org/FAQPage">Schema.org FAQPage</a> significantly improves visibility.</p>
        HTML;

        $analyzer = app(ContentAnalyzer::class);
        $result = $analyzer->analyze(
            'Generative Engine Optimization in Laravel — Complete Guide',
            'Learn how Generative Engine Optimization in Laravel boosts AI citations across Perplexity, ChatGPT, and Google AI Overviews.',
            $richGeoHtml,
            'Generative Engine Optimization'
        );

        $this->assertArrayHasKey('geo', $result);
        $this->assertSame(100, $result['geo']['score']);
        $this->assertSame('success', $result['geo']['checks']['direct_answer_blocks']['status']);
        $this->assertSame('success', $result['geo']['checks']['structured_formatting']['status']);
        $this->assertSame('success', $result['geo']['checks']['factual_density']['status']);
        $this->assertEmpty($result['geo']['recommendations']);

        // Compare against thin, unstructured prose lacking question headings, lists, and factual stats
        $thinGeo = (new SeoChecklist)->analyzeGeo('<p>We offer good services for businesses looking to grow online.</p>');
        $this->assertLessThan(50, $thinGeo['score']);
        $this->assertNotEmpty($thinGeo['recommendations']);
    }

    public function test_technical_seo_auditor_evaluates_geo_readiness_markup(): void
    {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <link rel="alternate" type="text/markdown" href="https://example.com/post?format=md">
            <script type="application/ld+json">
            {"@context":"https://schema.org","@type":"FAQPage","mainEntity":[]}
            </script>
        </head>
        <body>
            <main><article><h1>Title</h1></article></main>
        </body>
        </html>
        HTML;

        $auditor = new TechnicalSeoAuditor;
        $geoChecks = $auditor->auditGeoReadiness($html);

        $this->assertSame('success', $geoChecks['schema_graph']['status']);
        $this->assertSame('success', $geoChecks['markdown_alternate']['status']);
        $this->assertSame('success', $geoChecks['semantic_main']['status']);
    }

    public function test_package_views_and_components_render_accessible_modern_design_system(): void
    {
        $appHtml = view('quinrel::app')->render();
        $this->assertStringContainsString('color-scheme: light dark', $appHtml);
        $this->assertStringContainsString('light-dark(', $appHtml);
        $this->assertStringContainsString('text-wrap: balance', $appHtml);
        $this->assertStringContainsString('text-wrap: pretty', $appHtml);
        $this->assertStringContainsString('href="#quinrel-main"', $appHtml);

        $reportHtml = view('quinrel::reports.executive', [
            'report' => [
                'url' => 'https://example.com',
                'generated_at' => '2026-10-25T00:00:00Z',
                'overall_score' => 94,
                'pillars' => [
                    'pagespeed' => 91,
                    'indexing' => 96,
                    'technical_seo' => 95,
                    'accessibility' => 100,
                ],
                'action_plan' => [
                    [
                        'priority' => 2,
                        'pillar' => 'Performance',
                        'severity' => 'Warning',
                        'title' => 'Preload primary hero asset to optimize LCP.',
                        'remediation' => 'Add <link rel="preload" as="image"> to head.',
                    ],
                ],
            ],
        ])->render();

        $a11y = (new AccessibilityAuditor)->auditHtml($reportHtml);
        $this->assertSame(100, $a11y['score']);
        $this->assertSame(0, $a11y['counts']['Critical']);
        $this->assertEmpty($a11y['violations']);
        $this->assertStringContainsString('<caption', $reportHtml);
        $this->assertStringContainsString('scope="col"', $reportHtml);

        $sitemapComponent = view('quinrel::components.sitemap', [
            'links' => [
                ['canonical_url' => 'https://example.com/docs/seo', 'title' => 'SEO Guide', 'description' => 'Technical optimization reference'],
                ['canonical_url' => 'https://example.com/docs/geo', 'title' => 'GEO Guide', 'description' => 'Generative Engine Optimization'],
            ],
        ])->render();
        $this->assertStringContainsString('aria-label="Site directory"', $sitemapComponent);
        $this->assertStringContainsString('role="list"', $sitemapComponent);
        $this->assertStringContainsString('Generative Engine Optimization', $sitemapComponent);

        $disclosure = view('quinrel::components.ai-disclosure', [
            'model' => 'Gemini 2.5 Pro',
            'reviewedBy' => 'Editorial Team',
        ])->render();
        $this->assertStringContainsString('aria-label="AI Transparency Disclosure"', $disclosure);
        $this->assertStringContainsString('Gemini 2.5 Pro', $disclosure);
    }
}
