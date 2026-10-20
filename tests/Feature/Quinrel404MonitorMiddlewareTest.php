<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Quinrel\Laravel\Middleware\Quinrel404MonitorMiddleware;
use Quinrel\Laravel\Models\Quinrel404Log;
use Quinrel\Laravel\Models\QuinrelAuditLog;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Models\QuinrelRumMetric;
use Quinrel\Laravel\Tests\TestCase;
use Quinrel\Laravel\Traits\HasQuinrelMeta;
use Symfony\Component\HttpFoundation\Response;

class DummyArticle extends Model
{
    use HasQuinrelMeta;

    protected $guarded = [];
}

class Quinrel404MonitorMiddlewareTest extends TestCase
{
    public function test_it_logs_404_responses_and_increments_hits_on_repeat_requests(): void
    {
        $middleware = new Quinrel404MonitorMiddleware;

        $request1 = Request::create('/missing-page', 'GET');
        $request1->headers->set('referer', 'https://google.com');

        $response1 = $middleware->handle($request1, fn () => new Response('Not Found', 404));
        $this->assertSame(404, $response1->getStatusCode());

        $log = Quinrel404Log::where('url', '/missing-page')->first();
        $this->assertNotNull($log);
        $this->assertSame(1, $log->hits);
        $this->assertSame('https://google.com', $log->referrer);
        $this->assertNotNull($log->last_hit_at);

        // Second hit preserves or updates referrer and increments hits to 2
        $request2 = Request::create('/missing-page', 'GET');
        $middleware->handle($request2, fn () => new Response('Not Found', 404));

        $log->refresh();
        $this->assertSame(2, $log->hits);
        $this->assertSame('https://google.com', $log->referrer);
    }

    public function test_it_ignores_static_asset_404s(): void
    {
        $middleware = new Quinrel404MonitorMiddleware;

        $request = Request::create('/assets/app.js.map', 'GET');
        $middleware->handle($request, fn () => new Response('Not Found', 404));

        $this->assertSame(0, Quinrel404Log::count());
    }

    public function test_has_quinrel_meta_trait_falls_back_to_model_attributes(): void
    {
        $article = new DummyArticle([
            'title' => 'Fallback Article Title',
            'body' => '<p>This is a <strong>rich HTML</strong> body that should be stripped and used as fallback description.</p>',
            'featured_image' => 'https://example.com/cover.jpg',
        ]);

        $this->assertSame('Fallback Article Title', $article->getSeoTitle());
        $this->assertSame(
            'This is a rich HTML body that should be stripped and used as fallback description.',
            $article->getSeoDescription()
        );
        $this->assertSame('https://example.com/cover.jpg', $article->getSeoImage());
    }

    public function test_quinrel_meta_mass_assignment_and_audit_logging(): void
    {
        $meta = QuinrelMeta::create([
            'model_type' => DummyArticle::class,
            'model_id' => 42,
            'title' => 'Explicit SEO Title',
            'description' => 'Explicit SEO Description',
        ]);

        $this->assertSame(DummyArticle::class, $meta->model_type);
        $this->assertSame(42, $meta->model_id);

        $this->assertDatabaseHas('quinrel_audit_logs', [
            'event' => 'meta.created',
            'model_type' => DummyArticle::class,
            'model_id' => 42,
        ]);

        $meta->update(['title' => 'Updated SEO Title']);

        $this->assertSame(2, QuinrelAuditLog::count());
    }

    public function test_rum_metrics_prunable_query_removes_expired_records(): void
    {
        $old = new QuinrelRumMetric([
            'url' => 'https://example.com/old',
            'metric_name' => 'LCP',
            'metric_value' => 1200,
        ]);
        $old->created_at = now()->subDays(45);
        $old->updated_at = now()->subDays(45);
        $old->save();

        $recent = QuinrelRumMetric::create([
            'url' => 'https://example.com/recent',
            'metric_name' => 'LCP',
            'metric_value' => 900,
        ]);

        $prunableIds = (new QuinrelRumMetric)->prunable()->pluck('id')->all();

        $this->assertContains($old->id, $prunableIds);
        $this->assertNotContains($recent->id, $prunableIds);
    }
}
