<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Facades\Quinrel;
use Quinrel\Laravel\Services\Analytics\PageSpeedActionPlanService;
use Quinrel\Laravel\Services\Analytics\PageSpeedInsights;
use Quinrel\Laravel\Services\HeadRenderer;
use Quinrel\Laravel\Tests\TestCase;

class Epic3LighthouseAndPerformanceTest extends TestCase
{
    public function test_ingests_expanded_lighthouse_opportunities_and_builds_laravel_action_plan(): void
    {
        Http::fake([
            'www.googleapis.com/pagespeedonline/v5/runPagespeed*' => Http::response([
                'originLoadingExperience' => [
                    'metrics' => [
                        'LARGEST_CONTENTFUL_PAINT_MS' => ['percentile' => 3400],
                        'INTERACTION_TO_NEXT_PAINT' => ['percentile' => 280],
                        'CUMULATIVE_LAYOUT_SHIFT_SCORE' => ['percentile' => 0.18],
                    ],
                ],
                'lighthouseResult' => [
                    'categories' => [
                        'performance' => ['score' => 0.68],
                        'seo' => ['score' => 0.92],
                        'best-practices' => ['score' => 0.88],
                    ],
                    'audits' => [
                        'render-blocking-resources' => [
                            'details' => [
                                'overallSavingsMs' => 420,
                                'items' => [
                                    ['url' => 'https://example.com/css/app.css', 'totalBytes' => 45000, 'wastedMs' => 420],
                                ],
                            ],
                        ],
                        'largest-contentful-paint-element' => [
                            'details' => [
                                'items' => [
                                    [
                                        'node' => [
                                            'snippet' => '<img src="/images/hero-banner.webp" class="hero">',
                                            'selector' => 'main > section > img.hero',
                                            'nodeLabel' => 'Hero Banner',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'layout-shift-elements' => [
                            'details' => [
                                'items' => [
                                    [
                                        'node' => [
                                            'snippet' => '<div class="promo-banner">',
                                            'selector' => 'div.promo-banner',
                                        ],
                                        'score' => 0.14,
                                    ],
                                ],
                            ],
                        ],
                        'unminified-css' => [
                            'details' => [
                                'overallSavingsBytes' => 14336,
                                'overallSavingsMs' => 90,
                                'items' => [
                                    ['url' => 'https://example.com/css/legacy.css', 'wastedBytes' => 14336, 'wastedMs' => 90],
                                ],
                            ],
                        ],
                        'uses-optimized-images' => [
                            'details' => [
                                'overallSavingsBytes' => 81920,
                                'overallSavingsMs' => 210,
                                'items' => [
                                    ['url' => 'https://example.com/images/raw.png', 'wastedBytes' => 81920, 'wastedMs' => 210],
                                ],
                            ],
                        ],
                        'long-tasks' => [
                            'details' => [
                                'items' => [
                                    ['url' => 'https://www.googletagmanager.com/gtm.js', 'duration' => 145.0, 'startTime' => 410.0],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $psi = app(PageSpeedInsights::class);
        $audit = $psi->analyze('https://example.com');

        $this->assertNotNull($audit);
        $this->assertSame(68, $audit->performanceScore);
        $this->assertSame(92, $audit->seoScore);
        $this->assertSame(88, $audit->bestPracticesScore);
        $this->assertSame(420, $audit->renderBlockingResources['wasted_ms']);
        $this->assertTrue($audit->isLcpImage());
        $this->assertCount(1, $audit->layoutShiftElements);
        $this->assertSame(14336, $audit->unminifiedAssets['wasted_bytes']);
        $this->assertSame(81920, $audit->unoptimizedImages['wasted_bytes']);
        $this->assertCount(1, $audit->longTasks);

        $planService = app(PageSpeedActionPlanService::class);
        $plans = $planService->generate($audit);

        $auditIds = array_column($plans, 'audit_id');
        $this->assertContains('largest-contentful-paint-element', $auditIds);
        $this->assertContains('render-blocking-resources', $auditIds);
        $this->assertContains('layout-shift-elements', $auditIds);
        $this->assertContains('long-tasks', $auditIds);
        $this->assertContains('uses-optimized-images', $auditIds);
        $this->assertContains('unminified-assets', $auditIds);

        $lcpPlan = $plans[0];
        $this->assertStringContainsString("@quinrelPreload('/images/hero-banner.webp', 'image')", (string) $lcpPlan['blade_snippet']);
    }

    public function test_preload_directive_image_component_and_content_image_post_processor(): void
    {
        $preloadHtml = Blade::render("@quinrelPreload('/assets/hero.webp', 'image')");
        $this->assertSame('<link rel="preload" href="/assets/hero.webp" as="image" fetchpriority="high">', $preloadHtml);

        $lazyImg = Blade::render('<x-quinrel-image src="/photos/team.jpg" alt="Our Team" :width="800" :height="600" />');
        $this->assertStringContainsString('loading="lazy"', $lazyImg);
        $this->assertStringContainsString('decoding="async"', $lazyImg);
        $this->assertStringContainsString('width="800"', $lazyImg);
        $this->assertStringNotContainsString('fetchpriority="high"', $lazyImg);

        $heroImg = Blade::render('<x-quinrel::image src="/photos/hero.webp" alt="Hero" :hero="true" />');
        $this->assertStringContainsString('loading="eager"', $heroImg);
        $this->assertStringContainsString('fetchpriority="high"', $heroImg);
        $this->assertStringContainsString('decoding="async"', $heroImg);

        // Hero component also registers preload in HeadRenderer
        $headHtml = Quinrel::render();
        $this->assertStringContainsString('<link rel="preload" href="/photos/hero.webp" as="image" fetchpriority="high">', $headHtml);

        // Post body HTML image optimizer
        $rawBody = '<p><img src="/img/hero.jpg" alt="Hero" data-hero="true"><img src="/img/inline.jpg" alt="Inline"></p>';
        $optimized = app(HeadRenderer::class)->optimizeContentImages($rawBody);

        $this->assertStringContainsString('<img src="/img/hero.jpg" alt="Hero" data-hero="true" loading="eager" fetchpriority="high" decoding="async">', $optimized);
        $this->assertStringContainsString('<img src="/img/inline.jpg" alt="Inline" loading="lazy" decoding="async">', $optimized);
    }
}
