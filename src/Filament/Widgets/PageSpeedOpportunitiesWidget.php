<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Widgets;

use Filament\Widgets\Widget;
use Quinrel\Laravel\Services\Analytics\PageSpeedActionPlanService;
use Quinrel\Laravel\Services\Analytics\PageSpeedInsights;

class PageSpeedOpportunitiesWidget extends Widget
{
    protected static string $view = 'quinrel::filament.widgets.pagespeed-opportunities';

    public string $targetUrl = '';

    /** @var array<int, array<string, mixed>> */
    public array $opportunities = [];

    public ?int $performanceScore = null;

    public ?int $seoScore = null;

    public ?int $bestPracticesScore = null;

    public function mount(?string $url = null): void
    {
        $this->targetUrl = $url ?: (string) (config('quinrel.gsc.site_url') ?: config('app.url', 'https://example.com'));
        $this->loadOpportunities();
    }

    public function loadOpportunities(): void
    {
        if ($this->targetUrl === '') {
            return;
        }

        $audit = app(PageSpeedInsights::class)->analyze($this->targetUrl);
        if ($audit === null) {
            return;
        }

        $this->performanceScore = $audit->performanceScore;
        $this->seoScore = $audit->seoScore;
        $this->bestPracticesScore = $audit->bestPracticesScore;

        $actionPlans = app(PageSpeedActionPlanService::class)->generate($audit);
        $this->opportunities = array_slice($actionPlans, 0, 5);
    }
}
