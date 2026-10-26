<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Quinrel\Laravel\Jobs\DetectRedirectChains;
use Quinrel\Laravel\Models\Quinrel404Log;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Models\QuinrelRedirect;
use Quinrel\Laravel\Services\Technical\Remediation\AutoRedirectCreator;
use Quinrel\Laravel\Services\Technical\SeoCrawlActionPlanService;
use Quinrel\Laravel\Services\Technical\SiteCrawlOrchestrator;
use Quinrel\Laravel\Tests\TestCase;

class Epic2CrawlAndRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_crawl_orchestrator_streams_pages_and_generates_categorized_action_plan(): void
    {
        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 1,
            'title' => 'Home',
            'target_keyword' => 'Laravel SEO',
            'canonical_url' => 'https://example.com/home',
        ]);

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 2,
            'title' => 'Pricing',
            'target_keyword' => 'Laravel SEO',
            'canonical_url' => 'https://example.com/pricing',
        ]);

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 3,
            'title' => 'Broken Page',
            'canonical_url' => 'https://example.com/missing-page',
        ]);

        Http::fake([
            'https://example.com/home' => Http::response(
                '<!DOCTYPE html><html lang="en"><head>'
                .'<title>Complete Guide to Modern Laravel SEO Architecture</title>'
                .'<meta name="description" content="Learn how to build high-performance Laravel applications with structured metadata, sitemaps, and automated crawling.">'
                .'<link rel="canonical" href="https://example.com/shared-canonical">'
                .'<link rel="icon" href="/favicon.ico">'
                .'<link rel="apple-touch-icon" href="/apple-touch-icon.png">'
                .'</head><body><h1>Laravel SEO Architecture</h1><p>'.str_repeat('Comprehensive SEO content word ', 40).'</p>'
                .'<a href="https://example.com/home">Self</a></body></html>',
                200,
                ['Strict-Transport-Security' => 'max-age=31536000']
            ),
            'https://example.com/pricing' => Http::response(
                '<!DOCTYPE html><html lang="en"><head>'
                .'<link rel="canonical" href="https://example.com/shared-canonical">'
                .'</head><body><p>Short body without title or H1.</p></body></html>',
                200
            ),
            'https://example.com/missing-page' => Http::response('Not Found', 404),
        ]);

        $orchestrator = app(SiteCrawlOrchestrator::class);
        $crawlResult = $orchestrator->crawl(
            limit: 10,
            includeModels: false,
            chunkSize: 2,
            explicitUrls: [
                'https://example.com/home',
                'https://example.com/pricing',
                'https://example.com/missing-page',
            ]
        );

        $this->assertSame(3, $crawlResult['total_crawled']);
        $this->assertArrayHasKey('https://example.com/shared-canonical', $crawlResult['duplicate_canonicals']);
        $this->assertContains('https://example.com/pricing', $crawlResult['orphaned_urls']);

        $planService = app(SeoCrawlActionPlanService::class);
        $plan = $planService->buildActionPlan($crawlResult);

        $this->assertLessThan(100, $plan['score']);
        $this->assertGreaterThan(0, $plan['counts']['P0']);
        $this->assertGreaterThan(0, $plan['counts']['P1']);
        $this->assertGreaterThan(0, $plan['counts']['P2']);

        $categories = array_column($plan['tickets'], 'category');
        $this->assertContains('http_error', $categories);
        $this->assertContains('missing_title', $categories);
        $this->assertContains('duplicate_canonical', $categories);
        $this->assertContains('orphaned_content', $categories);
        $this->assertContains('missing_h1', $categories);

        $this->artisan('quinrel:crawl --limit=3 --chunk=2')
            ->assertExitCode(0);
    }

    public function test_auto_redirect_creator_matches_404_slug_and_creates_idempotent_redirect(): void
    {
        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 1,
            'title' => 'Laravel SEO Documentation',
            'canonical_url' => 'https://example.com/docs/laravel-seo-guide',
        ]);

        $log = Quinrel404Log::create([
            'url' => '/docs/laravel-seo-guied',
            'hits' => 12,
        ]);

        $creator = app(AutoRedirectCreator::class);
        $suggestion = $creator->suggestTarget('/docs/laravel-seo-guied');

        $this->assertNotNull($suggestion);
        $this->assertSame('/docs/laravel-seo-guide', $suggestion['target_url']);
        $this->assertGreaterThan(75.0, $suggestion['confidence']);

        $redirect = $creator->createFrom404($log);

        $this->assertNotNull($redirect);
        $this->assertSame('/docs/laravel-seo-guied', $redirect->source_url);
        $this->assertSame('/docs/laravel-seo-guide', $redirect->target_url);
        $this->assertSame(301, $redirect->status_code);
        $this->assertSame(0, Quinrel404Log::count());
    }

    public function test_auto_flatten_redirect_chains_flattens_chains_without_breaking_on_loops(): void
    {
        config(['quinrel.redirects.auto_flatten_chains' => true]);

        $r1 = QuinrelRedirect::create([
            'source_url' => '/step-1',
            'target_url' => '/step-2',
            'status_code' => 301,
        ]);

        $r2 = QuinrelRedirect::create([
            'source_url' => '/step-2',
            'target_url' => '/final-step',
            'status_code' => 301,
        ]);

        $loopA = QuinrelRedirect::create([
            'source_url' => '/cycle-a',
            'target_url' => '/cycle-b',
            'status_code' => 301,
        ]);

        QuinrelRedirect::create([
            'source_url' => '/cycle-b',
            'target_url' => '/cycle-a',
            'status_code' => 301,
        ]);

        DetectRedirectChains::dispatchSync();

        $r1->refresh();
        $this->assertSame('/final-step', $r1->target_url);
        $this->assertFalse($r1->is_chain);
        $this->assertFalse($r1->is_loop);
        $this->assertNull($r1->chain_details);

        $r2->refresh();
        $this->assertSame('/final-step', $r2->target_url);

        $loopA->refresh();
        $this->assertTrue($loopA->is_loop);
        $this->assertSame('/cycle-b', $loopA->target_url);
    }

    public function test_autofix_meta_command_drafts_missing_titles_and_descriptions_with_ai(): void
    {
        config([
            'quinrel.ai.provider' => 'openai',
            'quinrel.ai.openai_key' => 'sk-test-key',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'title' => 'AI Generated Title',
                                'description' => 'AI Generated Meta Description for search engines.',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $emptyMeta = QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 1,
            'title' => null,
            'description' => null,
            'target_keyword' => 'Laravel Package',
            'canonical_url' => 'https://example.com/empty',
        ]);

        $populatedMeta = QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 2,
            'title' => 'Existing Custom Title',
            'description' => 'Existing Custom Description',
            'canonical_url' => 'https://example.com/populated',
        ]);

        $this->artisan('quinrel:autofix-meta --empty-only')
            ->assertExitCode(0);

        $emptyMeta->refresh();
        $populatedMeta->refresh();

        $this->assertSame('AI Generated Title', $emptyMeta->title);
        $this->assertSame('AI Generated Meta Description for search engines.', $emptyMeta->description);
        $this->assertSame('Existing Custom Title', $populatedMeta->title);
    }
}
