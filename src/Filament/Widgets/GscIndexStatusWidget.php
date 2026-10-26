<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Quinrel\Laravel\Models\QuinrelMeta;
use Quinrel\Laravel\Services\Indexing\GoogleIndexingService;
use Quinrel\Laravel\Services\Tracking\GscIndexStatus;

class GscIndexStatusWidget extends Widget
{
    protected static string $view = 'quinrel::filament.widgets.gsc-index-status';

    public ?QuinrelMeta $record = null;

    public string $inspectionUrl = '';

    /** @var array<string, mixed>|null */
    public ?array $diagnosis = null;

    /** @var array<int, array<string, mixed>> */
    public array $actions = [];

    public ?string $rateLimitMessage = null;

    public function mount(?QuinrelMeta $record = null): void
    {
        $this->record = $record;
        $this->inspectionUrl = (string) ($record?->canonical_url ?: config('quinrel.gsc.site_url') ?: config('app.url', ''));

        if ($this->inspectionUrl !== '') {
            $cached = Cache::get($this->cacheKey($this->inspectionUrl));
            if (is_array($cached)) {
                $this->diagnosis = is_array($cached['diagnosis'] ?? null) ? $cached['diagnosis'] : null;
                $this->actions = is_array($cached['actions'] ?? null) ? $cached['actions'] : [];
            }
        }
    }

    public function inspectLiveUrl(?string $url = null): void
    {
        $targetUrl = trim($url ?? $this->inspectionUrl);
        if ($targetUrl === '') {
            return;
        }

        $this->inspectionUrl = $targetUrl;
        $limiterKey = 'quinrel:gsc-inspect:'.md5($targetUrl);

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            $this->rateLimitMessage = "Rate limit reached. Please wait {$seconds}s before inspecting again.";

            return;
        }

        RateLimiter::hit($limiterKey, 60);
        $this->rateLimitMessage = null;

        $result = app(GscIndexStatus::class)->inspectAndPlan($targetUrl, $this->record);
        $this->diagnosis = $result['diagnosis']->toArray();
        $this->actions = $result['actions'];

        Cache::put($this->cacheKey($targetUrl), [
            'diagnosis' => $this->diagnosis,
            'actions' => $this->actions,
        ], now()->addHour());
    }

    public function adoptGoogleCanonical(): void
    {
        $googleCanonical = is_string($this->diagnosis['google_canonical'] ?? null)
            ? $this->diagnosis['google_canonical']
            : null;

        if ($this->record === null || $googleCanonical === null) {
            return;
        }

        if (app(GoogleIndexingService::class)->adoptGoogleCanonical($this->record, $googleCanonical)) {
            $this->inspectionUrl = $googleCanonical;
            $this->diagnosis['user_canonical'] = $googleCanonical;
            $this->diagnosis['has_canonical_mismatch'] = false;
            $this->diagnosis['health_badge'] = ($this->diagnosis['is_indexed'] ?? false) ? 'green' : 'red';
            $this->diagnosis['health_label'] = ($this->diagnosis['is_indexed'] ?? false) ? 'Indexed' : 'Blocked / Error';
            $this->actions = array_values(array_filter(
                $this->actions,
                fn (array $action): bool => ($action['id'] ?? '') !== 'canonical_mismatch'
            ));
        }
    }

    protected function cacheKey(string $url): string
    {
        return 'quinrel.gsc.inspection.'.md5($url);
    }
}
