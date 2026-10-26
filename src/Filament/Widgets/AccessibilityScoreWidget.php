<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Accessibility\AccessibilityActionPlanService;
use Quinrel\Laravel\Services\Accessibility\AccessibilityAuditor;
use Quinrel\Laravel\Services\Media\AiAltTextGenerator;

class AccessibilityScoreWidget extends Widget
{
    protected static string $view = 'quinrel::filament.widgets.accessibility-score';

    public string $targetUrl = '';

    public int $score = 100;

    /** @var array{Critical: int, Serious: int, Moderate: int} */
    public array $counts = ['Critical' => 0, 'Serious' => 0, 'Moderate' => 0];

    /** @var array<int, array<string, mixed>> */
    public array $tickets = [];

    public function mount(?string $url = null): void
    {
        $this->targetUrl = $url ?: (string) config('app.url', 'https://example.com');
        $cached = Cache::get('quinrel.a11y.latest');

        if (is_array($cached) && is_array($cached['plan'] ?? null)) {
            $plan = $cached['plan'];
            $this->score = (int) ($plan['score'] ?? 100);
            $this->counts = is_array($plan['counts'] ?? null)
                ? $plan['counts']
                : ['Critical' => 0, 'Serious' => 0, 'Moderate' => 0];
            $this->tickets = is_array($plan['tickets'] ?? null) ? $plan['tickets'] : [];
        }
    }

    public function runAuditNow(): void
    {
        if ($this->targetUrl === '') {
            return;
        }

        $audit = app(AccessibilityAuditor::class)->auditUrl($this->targetUrl);
        $plan = app(AccessibilityActionPlanService::class)->generate($audit);

        $this->score = $plan['score'];
        $this->counts = $plan['counts'];
        $this->tickets = $plan['tickets'];

        Cache::put('quinrel.a11y.latest', [
            'audited_at' => now()->toIso8601String(),
            'url' => $this->targetUrl,
            'plan' => $plan,
        ], now()->addDays(7));
    }

    public function autofixAltTexts(): void
    {
        $generator = app(AiAltTextGenerator::class);
        foreach (QuinrelMeta::query()->limit(50)->get() as $meta) {
            $generator->autofixMetaRecord($meta);
        }
    }
}
