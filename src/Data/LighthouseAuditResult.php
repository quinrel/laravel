<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Data;

final readonly class LighthouseAuditResult
{
    /**
     * @param  array{wasted_ms: int, items: array<int, array{url: string, total_bytes: int, wasted_ms: int}>}  $renderBlockingResources
     * @param  array{snippet: string, selector: string, node_label: string}|null  $lcpElement
     * @param  array<int, array{snippet: string, selector: string, score: float}>  $layoutShiftElements
     * @param  array{wasted_bytes: int, wasted_ms: int, items: array<int, array{url: string, type: string, wasted_bytes: int, wasted_ms: int}>}  $unminifiedAssets
     * @param  array{wasted_bytes: int, wasted_ms: int, items: array<int, array{url: string, audit: string, wasted_bytes: int, wasted_ms: int}>}  $unoptimizedImages
     * @param  array<int, array{url: string, duration_ms: float, start_time_ms: float}>  $longTasks
     * @param  array<int, array{id: string, title: string, wasted_ms: int, wasted_bytes: int}>  $opportunities
     */
    public function __construct(
        public string $url,
        public string $strategy,
        public ?int $performanceScore,
        public ?int $seoScore,
        public ?int $bestPracticesScore,
        public ?float $lcpMs,
        public ?float $inpMs,
        public ?float $clsScore,
        public array $renderBlockingResources,
        public ?array $lcpElement,
        public array $layoutShiftElements,
        public array $unminifiedAssets,
        public array $unoptimizedImages,
        public array $longTasks,
        public array $opportunities = [],
    ) {}

    public function isLcpImage(): bool
    {
        if ($this->lcpElement === null) {
            return false;
        }

        $snippet = strtolower($this->lcpElement['snippet']);
        $selector = strtolower($this->lcpElement['selector']);

        return str_contains($snippet, '<img') || str_contains($selector, 'img');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'strategy' => $this->strategy,
            'score' => $this->performanceScore,
            'performance_score' => $this->performanceScore,
            'seo_score' => $this->seoScore,
            'best_practices_score' => $this->bestPracticesScore,
            'lcp' => $this->lcpMs,
            'inp' => $this->inpMs,
            'cls' => $this->clsScore,
            'render_blocking_resources' => $this->renderBlockingResources,
            'lcp_element' => $this->lcpElement,
            'layout_shift_elements' => $this->layoutShiftElements,
            'unminified_assets' => $this->unminifiedAssets,
            'unoptimized_images' => $this->unoptimizedImages,
            'long_tasks' => $this->longTasks,
            'opportunities' => $this->opportunities,
        ];
    }
}
