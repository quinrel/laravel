<?php

declare(strict_types=1);

namespace Quinrel\Laravel;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Quinrel\Laravel\Console\Commands\AuditAccessibilityCommand;
use Quinrel\Laravel\Console\Commands\AuditAllCommand;
use Quinrel\Laravel\Console\Commands\AutofixMetaCommand;
use Quinrel\Laravel\Console\Commands\CiAuditCheckCommand;
use Quinrel\Laravel\Console\Commands\CrawlSiteCommand;
use Quinrel\Laravel\Console\Commands\FetchGscDataCommand;
use Quinrel\Laravel\Console\Commands\GenerateImageAltTextCommand;
use Quinrel\Laravel\Console\Commands\GenerateSitemapCommand;
use Quinrel\Laravel\Console\Commands\InstallCommand;
use Quinrel\Laravel\Console\Commands\MigrateCompetitorCommand;
use Quinrel\Laravel\Middleware\AeoContentNegotiationMiddleware;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Observers\QuinrelMetaObserver;
use Quinrel\Laravel\Services\Accessibility\AccessibilityActionPlanService;
use Quinrel\Laravel\Services\Accessibility\AccessibilityAuditor;
use Quinrel\Laravel\Services\AI\AiWritingAssistant;
use Quinrel\Laravel\Services\Analytics\KeywordCannibalizationDetector;
use Quinrel\Laravel\Services\Analytics\PageSpeedActionPlanService;
use Quinrel\Laravel\Services\HeadRenderer;
use Quinrel\Laravel\Services\Media\AiAltTextGenerator;
use Quinrel\Laravel\Services\Reporting\ClientReportGenerator;
use Quinrel\Laravel\Services\Reporting\UnifiedAuditCoordinator;
use Quinrel\Laravel\Services\SeoRouteManager;
use Quinrel\Laravel\Services\Technical\Remediation\AutoRedirectCreator;
use Quinrel\Laravel\Services\Technical\SeoCrawlActionPlanService;
use Quinrel\Laravel\Services\Technical\SiteCrawlOrchestrator;
use Quinrel\Laravel\Services\Tracking\GscActionPlanGenerator;
use Quinrel\Laravel\Services\Tracking\GscInspectionNormalizer;
use Quinrel\Laravel\View\Components\Image;

class QuinrelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/quinrel.php', 'quinrel');

        $this->app->singleton(SeoRouteManager::class);
        $this->app->scoped(HeadRenderer::class);
        $this->app->alias(HeadRenderer::class, 'quinrel');
        $this->app->singleton(ClientReportGenerator::class);
        $this->app->singleton(UnifiedAuditCoordinator::class);
        $this->app->singleton(KeywordCannibalizationDetector::class);
        $this->app->singleton(AiWritingAssistant::class);
        $this->app->singleton(AiAltTextGenerator::class);
        $this->app->singleton(GscInspectionNormalizer::class);
        $this->app->singleton(GscActionPlanGenerator::class);
        $this->app->singleton(SiteCrawlOrchestrator::class);
        $this->app->singleton(SeoCrawlActionPlanService::class);
        $this->app->singleton(AutoRedirectCreator::class);
        $this->app->singleton(PageSpeedActionPlanService::class);
        $this->app->singleton(AccessibilityAuditor::class);
        $this->app->singleton(AccessibilityActionPlanService::class);
    }

    public function boot(): void
    {
        if ($this->app->bound('router')) {
            /** @var Router $router */
            $router = $this->app->make('router');
            $router->aliasMiddleware('quinrel.aeo', AeoContentNegotiationMiddleware::class);
        }

        RoutingRoute::macro('seo', function (array $seoData) {
            /** @var RoutingRoute $this */
            $uri = $this->uri();
            $action = $this->getAction();
            $action['quinrel_seo'] = $seoData;
            $this->setAction($action);

            app(SeoRouteManager::class)->register($uri, $seoData);

            return $this;
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateCompetitorCommand::class,
                InstallCommand::class,
                FetchGscDataCommand::class,
                GenerateSitemapCommand::class,
                CrawlSiteCommand::class,
                AutofixMetaCommand::class,
                GenerateImageAltTextCommand::class,
                AuditAccessibilityCommand::class,
                AuditAllCommand::class,
                CiAuditCheckCommand::class,
            ]);

            if (class_exists(AboutCommand::class)) {
                AboutCommand::add('Quinrel SEO', fn () => [
                    'UI Driver' => (string) config('quinrel.ui.driver', 'standalone'),
                    'AI Provider' => (string) config('quinrel.ai.provider', 'ollama'),
                    'IndexNow' => config('quinrel.indexnow.enabled') ? 'Enabled' : 'Disabled',
                    'Google Indexing' => config('quinrel.google_indexing.enabled') ? 'Enabled' : 'Disabled',
                ]);
            }

            $this->app->booted(function () {
                if (config('quinrel.api.gsc_service_account') || config('quinrel.api.google_oauth_token')) {
                    $schedule = $this->app->make(Schedule::class);
                    $schedule->command('quinrel:fetch-gsc-data')->dailyAt('03:00');
                }
            });

            $this->publishes([
                __DIR__.'/../config/quinrel.php' => config_path('quinrel.php'),
            ], 'quinrel-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'quinrel-migrations');

            $this->publishes([
                __DIR__.'/../public/build' => public_path('vendor/quinrel/build'),
            ], 'quinrel-assets');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'quinrel');

        Blade::directive('quinrelHead', function (?string $expression) {
            $expression = $expression ?: 'null';

            return "<?php echo app(\Quinrel\Laravel\Services\HeadRenderer::class)->render({$expression}); ?>";
        });

        Blade::directive('quinrelPreload', function (?string $expression) {
            return "<?php echo app(\Quinrel\Laravel\Services\HeadRenderer::class)->renderPreload({$expression}); ?>";
        });

        Blade::directive('quinrelRum', function () {
            $endpointExpr = '<?php echo e(route(\'quinrel.api.rum.store\')); ?>';
            $csrfExpr = '<?php echo e(csrf_token()); ?>';
            $sampleRateExpr = '<?php echo json_encode((float) config(\'quinrel.rum.sample_rate\', 1.0)); ?>';

            return <<<HTML
<script>
(function() {
  var sampleRate = {$sampleRateExpr};
  if (Math.random() > sampleRate || typeof PerformanceObserver === 'undefined') return;

  var endpoint = '{$endpointExpr}';
  var csrf = '{$csrfExpr}';
  var sessionId = (typeof crypto !== 'undefined' && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now());
  var metrics = {};
  var flushed = false;

  function rate(name, val) {
    if (name === 'LCP') return val <= 2500 ? 'good' : (val <= 4000 ? 'needs-improvement' : 'poor');
    if (name === 'INP') return val <= 200 ? 'good' : (val <= 500 ? 'needs-improvement' : 'poor');
    if (name === 'CLS') return val <= 0.1 ? 'good' : (val <= 0.25 ? 'needs-improvement' : 'poor');
    if (name === 'FCP') return val <= 1800 ? 'good' : (val <= 3000 ? 'needs-improvement' : 'poor');
    if (name === 'TTFB') return val <= 800 ? 'good' : (val <= 1800 ? 'needs-improvement' : 'poor');
    return 'good';
  }

  function record(name, val) {
    metrics[name] = { metric_name: name, metric_value: Number(val.toFixed(4)), rating: rate(name, val) };
  }

  try {
    new PerformanceObserver(function(l) {
      var entries = l.getEntries();
      if (entries.length) record('LCP', entries[entries.length - 1].startTime);
    }).observe({ type: 'largest-contentful-paint', buffered: true });
  } catch (e) {}

  try {
    var clsVal = 0;
    new PerformanceObserver(function(l) {
      l.getEntries().forEach(function(entry) {
        if (!entry.hadRecentInput) { clsVal += entry.value; record('CLS', clsVal); }
      });
    }).observe({ type: 'layout-shift', buffered: true });
  } catch (e) {}

  try {
    new PerformanceObserver(function(l) {
      l.getEntries().forEach(function(entry) {
        if (entry.name === 'first-contentful-paint') record('FCP', entry.startTime);
      });
    }).observe({ type: 'paint', buffered: true });
  } catch (e) {}

  try {
    var maxInp = 0;
    new PerformanceObserver(function(l) {
      l.getEntries().forEach(function(entry) {
        if (entry.duration > maxInp) { maxInp = entry.duration; record('INP', maxInp); }
      });
    }).observe({ type: 'event', buffered: true, durationThreshold: 40 });
  } catch (e) {}

  try {
    var nav = performance.getEntriesByType('navigation')[0];
    if (nav && nav.responseStart > 0) record('TTFB', nav.responseStart);
  } catch (e) {}

  function flush() {
    if (flushed) return;
    var list = Object.values(metrics);
    if (!list.length) return;
    flushed = true;
    var body = JSON.stringify({ url: window.location.href, session_id: sessionId, metrics: list });
    if (navigator.sendBeacon) {
      navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }));
    } else {
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: body,
        keepalive: true
      });
    }
  }

  document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden') flush();
  });
  window.addEventListener('pagehide', flush);
})();
</script>
HTML;
        });

        Blade::componentNamespace('Quinrel\\Laravel\\View\\Components', 'quinrel');
        Blade::component(Image::class, 'quinrel-image');
        Blade::component('quinrel::components.cookie-notice', 'quinrel-cookie-notice');
        Blade::component('quinrel::components.ai-disclosure', 'quinrel-ai-disclosure');

        QuinrelMeta::observe(QuinrelMetaObserver::class);

        $this->registerGates();
    }

    protected function registerGates(): void
    {
        if (! Gate::has('view-quinrel')) {
            Gate::define('view-quinrel', function (?object $user = null) {
                if ($this->app->environment('local')) {
                    return true;
                }

                return $user !== null && in_array($user->role ?? null, ['admin', 'editor', 'author', 'seo'], true);
            });
        }

        if (! Gate::has('manage-quinrel')) {
            Gate::define('manage-quinrel', function (?object $user = null) {
                if ($this->app->environment('local')) {
                    return true;
                }

                return $user !== null && in_array($user->role ?? null, ['admin', 'seo'], true);
            });
        }
    }
}
