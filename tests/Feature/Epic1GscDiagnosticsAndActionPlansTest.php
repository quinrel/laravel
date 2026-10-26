<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Quinrel\Laravel\Jobs\PingGoogleIndexingJob;
use Quinrel\Laravel\Jobs\PingIndexNowJob;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Indexing\GoogleIndexingService;
use Quinrel\Laravel\Services\Tracking\GscActionPlanGenerator;
use Quinrel\Laravel\Services\Tracking\GscIndexStatus;
use Quinrel\Laravel\Services\Tracking\GscInspectionNormalizer;
use Quinrel\Laravel\Tests\TestCase;

class Epic1GscDiagnosticsAndActionPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_prioritized_action_plans_for_each_gsc_failure_state(): void
    {
        File::put(public_path('robots.txt'), "User-agent: *\nDisallow: /private/\n");

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 1,
            'title' => 'Laravel SEO Guide',
            'description' => 'Comprehensive Laravel SEO Architecture',
            'target_keyword' => 'Laravel SEO',
            'canonical_url' => 'https://example.com/laravel-seo-guide',
        ]);

        $normalizer = new GscInspectionNormalizer;
        $generator = app(GscActionPlanGenerator::class);

        // 1. Blocked by robots.txt
        $robotsDiag = $normalizer->normalize('https://example.com/private/roadmap', [
            'inspectionResult' => [
                'indexStatusResult' => [
                    'verdict' => 'FAIL',
                    'coverageState' => 'Blocked by robots.txt',
                    'robotsTxtState' => 'DISALLOWED',
                ],
            ],
        ]);
        $robotsPlan = $generator->generate($robotsDiag);
        $this->assertNotEmpty($robotsPlan);
        $this->assertSame('robots_txt_blocked', $robotsPlan[0]['id']);
        $this->assertSame('Critical', $robotsPlan[0]['severity']);
        $this->assertSame('Disallow: /private/', $robotsPlan[0]['metadata']['conflicting_rule']);

        // 2. Canonical Mismatch
        $canonDiag = $normalizer->normalize('https://example.com/dup-page', [
            'inspectionResult' => [
                'indexStatusResult' => [
                    'verdict' => 'NEUTRAL',
                    'coverageState' => 'Duplicate without user-selected canonical',
                    'userCanonical' => 'https://example.com/dup-page',
                    'googleCanonical' => 'https://example.com/main-page',
                ],
            ],
        ]);
        $canonPlan = $generator->generate($canonDiag);
        $this->assertSame('canonical_mismatch', $canonPlan[0]['id']);
        $this->assertSame('adopt_google_canonical', $canonPlan[0]['autofix_action']);

        // 3. Crawled - Currently Not Indexed
        $crawledMeta = QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 2,
            'title' => 'Thin Laravel SEO Post',
            'target_keyword' => 'Laravel SEO',
            'canonical_url' => 'https://example.com/thin-post',
        ]);
        $crawledDiag = $normalizer->normalize('https://example.com/thin-post', [
            'inspectionResult' => [
                'indexStatusResult' => [
                    'verdict' => 'NEUTRAL',
                    'coverageState' => 'Crawled - currently not indexed',
                ],
            ],
        ]);
        $crawledPlan = $generator->generate($crawledDiag, $crawledMeta, 'Short thin content.');
        $this->assertSame('crawled_not_indexed', $crawledPlan[0]['id']);
        $this->assertTrue($crawledPlan[0]['metadata']['is_thin_content']);
        $this->assertNotEmpty($crawledPlan[0]['metadata']['suggested_internal_links']);

        // 4. Soft 404 / 404
        $soft404Diag = $normalizer->normalize('https://example.com/gone-page', [
            'inspectionResult' => [
                'indexStatusResult' => [
                    'verdict' => 'FAIL',
                    'coverageState' => 'Soft 404',
                    'pageFetchState' => 'SOFT_404',
                ],
            ],
        ]);
        $soft404Plan = $generator->generate($soft404Diag);
        $this->assertSame('soft_or_hard_404', $soft404Plan[0]['id']);
        $this->assertSame('create_redirect', $soft404Plan[0]['autofix_action']);

        @unlink(public_path('robots.txt'));
    }

    public function test_adopt_google_canonical_updates_meta_and_dispatches_indexing_jobs(): void
    {
        Queue::fake();

        config([
            'quinrel.api.google_oauth_token' => 'ya29.test',
            'quinrel.gsc.site_url' => 'https://example.com',
        ]);

        Http::fake([
            'searchconsole.googleapis.com/v1/urlInspection/index:inspect' => Http::response([
                'inspectionResult' => [
                    'indexStatusResult' => [
                        'verdict' => 'NEUTRAL',
                        'coverageState' => 'Duplicate, Google chose different canonical than user',
                        'userCanonical' => 'https://example.com/old-canonical',
                        'googleCanonical' => 'https://example.com/authoritative-canonical',
                    ],
                ],
            ], 200),
        ]);

        $meta = QuinrelMeta::withoutEvents(fn () => QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 10,
            'canonical_url' => 'https://example.com/old-canonical',
            'robots' => 'noindex, follow',
        ]));

        $service = app(GoogleIndexingService::class);
        $adopted = $service->adoptGoogleCanonical($meta);

        $this->assertTrue($adopted);
        $meta->refresh();
        $this->assertSame('https://example.com/authoritative-canonical', $meta->canonical_url);
        $this->assertSame('index, follow', $meta->robots);

        Queue::assertPushed(PingGoogleIndexingJob::class, fn ($job) => $job->url === 'https://example.com/authoritative-canonical');
        Queue::assertPushed(PingIndexNowJob::class, fn ($job) => $job->urls === ['https://example.com/authoritative-canonical']);
    }

    public function test_observer_dispatches_resubmission_jobs_when_excluded_record_is_resolved(): void
    {
        Queue::fake();

        $meta = QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 55,
            'canonical_url' => 'https://example.com/draft-page',
            'robots' => 'noindex, nofollow',
        ]);

        Queue::assertNothingPushed();

        // Resolve indexing exclusion
        $meta->update(['robots' => 'index, follow']);

        Queue::assertPushed(PingGoogleIndexingJob::class, fn ($job) => $job->url === 'https://example.com/draft-page');
        Queue::assertPushed(PingIndexNowJob::class, fn ($job) => $job->urls === ['https://example.com/draft-page']);
    }

    public function test_gsc_index_status_inspect_and_plan_combines_diagnosis_and_actions(): void
    {
        config([
            'quinrel.api.google_oauth_token' => 'ya29.test',
            'quinrel.gsc.site_url' => 'https://example.com',
        ]);

        Http::fake([
            'searchconsole.googleapis.com/v1/urlInspection/index:inspect' => Http::response([
                'inspectionResult' => [
                    'indexStatusResult' => [
                        'verdict' => 'FAIL',
                        'coverageState' => 'Excluded by ‘noindex’ tag',
                        'indexingState' => 'BLOCKED_BY_META_TAG',
                    ],
                ],
            ], 200),
        ]);

        $result = app(GscIndexStatus::class)->inspectAndPlan('https://example.com/blocked-page');

        $this->assertTrue($result['diagnosis']->isBlockedByNoindex());
        $this->assertNotEmpty($result['actions']);
        $this->assertSame('noindex_directive', $result['actions'][0]['id']);
    }
}
