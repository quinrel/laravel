<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Quinrel\Laravel\Models\QuinrelGscMetric;
use Quinrel\Laravel\Models\QuinrelIndexingLog;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\AI\AiWritingAssistant;
use Quinrel\Laravel\Services\Analytics\KeywordCannibalizationDetector;
use Quinrel\Laravel\Services\IndexNow\IndexNowService;
use Quinrel\Laravel\Services\Migration\MigrationService;
use Quinrel\Laravel\Services\Technical\OrphanedContentFinder;
use Quinrel\Laravel\Services\Tracking\GscIndexStatus;
use Quinrel\Laravel\Tests\TestCase;

class Phase4ServicesAndFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_writing_assistant_calls_anthropic_messages_api(): void
    {
        config([
            'quinrel.ai.provider' => 'anthropic',
            'quinrel.ai.anthropic_key' => 'sk-ant-test-key',
        ]);

        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode([
                            'title' => 'Claude SEO Title',
                            'description' => 'Claude SEO Description',
                        ]),
                    ],
                ],
            ], 200),
        ]);

        $assistant = new AiWritingAssistant;
        $result = $assistant->suggestMetadata('Comprehensive guide to Laravel SEO.', 'Laravel SEO');

        $this->assertSame('Claude SEO Title', $result['title']);
        $this->assertSame('Claude SEO Description', $result['description']);
    }

    public function test_migration_service_migrates_records_from_ralphjsmit_seo_table(): void
    {
        Schema::create('seo', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('robots')->nullable();
            $table->string('canonical_url')->nullable();
        });

        DB::table('seo')->insert([
            'model_type' => User::class,
            'model_id' => 42,
            'title' => 'Migrated Title',
            'description' => 'Migrated Description',
            'image' => 'https://example.com/og.png',
            'robots' => 'index, follow',
            'canonical_url' => 'https://example.com/users/42',
        ]);

        $migrator = new MigrationService;
        $this->assertTrue($migrator->migrateFrom('ralphjsmit/laravel-seo'));

        $this->assertDatabaseHas('quinrel_meta', [
            'model_type' => User::class,
            'model_id' => 42,
            'title' => 'Migrated Title',
            'canonical_url' => 'https://example.com/users/42',
        ]);
    }

    public function test_orphaned_content_finder_identifies_unlinked_models(): void
    {
        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 1,
            'title' => 'Home Page',
            'canonical_url' => 'https://example.com/home',
            'description' => 'Check out <a href="https://example.com/linked-page">our linked page</a>.',
        ]);

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 2,
            'title' => 'Linked Page',
            'canonical_url' => 'https://example.com/linked-page',
            'description' => 'Back to <a href="https://example.com/home">home</a>.',
        ]);

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 3,
            'title' => 'Orphaned Page',
            'canonical_url' => 'https://example.com/orphan',
            'description' => 'No incoming links point here.',
        ]);

        $finder = new OrphanedContentFinder;
        $orphans = $finder->find(QuinrelMeta::class, 'canonical_url');

        $this->assertCount(1, $orphans);
        /** @var QuinrelMeta $orphan */
        $orphan = $orphans->first();
        $this->assertSame('https://example.com/orphan', $orphan->canonical_url);
    }

    public function test_keyword_cannibalization_detector_uses_sql_grouping(): void
    {
        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 1,
            'target_keyword' => 'Laravel SEO',
            'canonical_url' => 'https://example.com/1',
        ]);

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 2,
            'target_keyword' => 'laravel seo ',
            'canonical_url' => 'https://example.com/2',
        ]);

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 3,
            'target_keyword' => 'Unique Keyword',
            'canonical_url' => 'https://example.com/3',
        ]);

        $detector = new KeywordCannibalizationDetector;
        $duplicates = $detector->detect();

        $this->assertCount(1, $duplicates);
        $this->assertTrue($duplicates->has('laravel seo'));
        $this->assertCount(2, $duplicates->get('laravel seo'));
    }

    public function test_gsc_index_status_and_batched_fetch_command(): void
    {
        // 1. Without credentials, command exits cleanly with SUCCESS (0)
        config([
            'quinrel.api.gsc_service_account' => null,
            'quinrel.api.google_oauth_token' => null,
        ]);

        $this->artisan('quinrel:fetch-gsc-data')->assertExitCode(0);

        // 2. With credentials, GscIndexStatus and FetchGscDataCommand work via Google APIs
        config([
            'quinrel.api.google_oauth_token' => 'ya29.test-token',
            'quinrel.gsc.site_url' => 'https://example.com',
        ]);

        Http::fake([
            'searchconsole.googleapis.com/v1/urlInspection/index:inspect' => Http::response([
                'inspectionResult' => [
                    'indexStatusResult' => [
                        'verdict' => 'PASS',
                        'coverageState' => 'Submitted and indexed',
                    ],
                ],
            ], 200),
            'searchconsole.googleapis.com/webmasters/v3/sites/*/searchAnalytics/query' => Http::response([
                'rows' => [
                    [
                        'keys' => ['https://example.com/page-1'],
                        'clicks' => 25,
                        'impressions' => 500,
                        'ctr' => 0.05,
                        'position' => 3.2,
                    ],
                ],
            ], 200),
        ]);

        $status = (new GscIndexStatus)->check('https://example.com/page-1');
        $this->assertSame('PASS', $status['inspectionResult']['indexStatusResult']['verdict']);

        $this->artisan('quinrel:fetch-gsc-data --date=2026-01-15')->assertExitCode(0);

        $this->assertDatabaseHas('quinrel_gsc_metrics', [
            'url' => 'https://example.com/page-1',
            'date' => '2026-01-15 00:00:00',
            'clicks' => 25,
        ]);
        $this->assertSame(2, QuinrelGscMetric::count());
    }

    public function test_generate_sitemaps_command_and_indexnow_logging(): void
    {
        Storage::fake('public');

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 10,
            'title' => 'Breaking News',
            'canonical_url' => 'https://example.com/news/1',
            'robots' => 'index, follow',
        ]);

        $this->artisan('quinrel:generate-sitemaps --disk=public --news')
            ->assertExitCode(0);

        Storage::disk('public')->assertExists('sitemap.xml');
        Storage::disk('public')->assertExists('sitemap-news.xml');

        config([
            'quinrel.indexnow.enabled' => true,
            'quinrel.indexnow.key' => 'testkey123456',
            'quinrel.indexnow.search_engines' => ['api.indexnow.org'],
        ]);

        Http::fake([
            'api.indexnow.org/indexnow' => Http::response([], 200),
        ]);

        $indexNow = new IndexNowService;
        $this->assertTrue($indexNow->submit(['https://example.com/news/1']));

        $this->assertSame(1, QuinrelIndexingLog::where('engine', 'indexnow')->count());
    }

    public function test_standalone_dashboard_and_headless_api_routes(): void
    {
        $admin = new User;
        $admin->id = 1;
        $admin->setAttribute('role', 'admin');

        $response = $this->actingAs($admin)->get('/quinrel');
        $response->assertOk();
        $response->assertSee('<meta name="csrf-token"', false);
        $response->assertDontSee('http://localhost:5173', false);

        QuinrelMeta::create([
            'model_type' => User::class,
            'model_id' => 99,
            'title' => 'Headless Page',
            'description' => 'Headless Description',
            'canonical_url' => 'https://example.com/headless',
            'custom_schema' => ['@type' => 'WebPage'],
        ]);

        $this->getJson('/quinrel/api/v1/seo/99?model_type='.urlencode(User::class))
            ->assertOk()
            ->assertJsonPath('title', 'Headless Page')
            ->assertJsonPath('schema.@type', 'WebPage');
    }
}
