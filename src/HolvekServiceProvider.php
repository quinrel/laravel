<?php

declare(strict_types=1);

namespace Holvek\Laravel;

use Holvek\Laravel\Console\Commands\MigrateCompetitorCommand;
use Holvek\Laravel\Models\HolvekMeta;
use Holvek\Laravel\Observers\HolvekMetaObserver;
use Holvek\Laravel\Services\AI\AiWritingAssistant;
use Holvek\Laravel\Services\Analytics\KeywordCannibalizationDetector;
use Holvek\Laravel\Services\Reporting\ClientReportGenerator;
use Holvek\Laravel\Services\SeoRouteManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class HolvekServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/holvek.php', 'holvek');

        $this->app->singleton(SeoRouteManager::class);
        $this->app->singleton(ClientReportGenerator::class);
        $this->app->singleton(KeywordCannibalizationDetector::class);
        $this->app->singleton(AiWritingAssistant::class);
    }

    public function boot(): void
    {
        Route::macro('seo', function (array $seoData) {
            /** @var \Illuminate\Routing\Route $this */
            $uri = $this->uri();
            app(SeoRouteManager::class)->register($uri, $seoData);

            return $this;
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateCompetitorCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/holvek.php' => config_path('holvek.php'),
            ], 'holvek-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'holvek-migrations');

            $this->publishes([
                __DIR__.'/../public/build' => public_path('vendor/holvek/build'),
            ], 'holvek-assets');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'holvek');

        Blade::directive('holvekHead', function (?string $expression) {
            $expression = $expression ?: 'null';

            return "<?php echo app(\Holvek\Laravel\Services\HeadRenderer::class)->render({$expression}); ?>";
        });

        Blade::componentNamespace('Holvek\\Laravel\\View\\Components', 'holvek');
        Blade::component('holvek::components.cookie-notice', 'holvek-cookie-notice');
        Blade::component('holvek::components.ai-disclosure', 'holvek-ai-disclosure');

        HolvekMeta::observe(HolvekMetaObserver::class);

        $this->registerGates();
    }

    protected function registerGates(): void
    {
        Gate::define('view-holvek', function (object $user) {
            return in_array($user->role ?? null, ['admin', 'editor', 'author', 'seo'], true);
        });

        Gate::define('manage-holvek', function (object $user) {
            return in_array($user->role ?? null, ['admin', 'seo'], true);
        });
    }
}
