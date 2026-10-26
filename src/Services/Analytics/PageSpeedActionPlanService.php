<?php

declare(strict_types=1);

namespace Quinrel\Laravel\Services\Analytics;

use Quinrel\Laravel\Data\LighthouseAuditResult;

class PageSpeedActionPlanService
{
    /**
     * Translate Lighthouse audit opportunities and diagnostics into Laravel-specific remediation plans.
     *
     * @return array<int, array{
     *     audit_id: string,
     *     severity: string,
     *     title: string,
     *     wasted_ms: int,
     *     wasted_bytes: int,
     *     recommendation: string,
     *     blade_snippet: ?string,
     *     affected_items: array<int, mixed>
     * }>
     */
    public function generate(LighthouseAuditResult $audit): array
    {
        $plans = [];

        // 1. LCP Element is an <img>
        if ($audit->isLcpImage() && $audit->lcpElement !== null) {
            $imgUrl = $this->extractSrcFromSnippet($audit->lcpElement['snippet']) ?? '/images/hero.webp';
            $lcpSeverity = ($audit->lcpMs !== null && $audit->lcpMs > 2500.0) ? 'Critical' : 'Warning';

            $plans[] = [
                'audit_id' => 'largest-contentful-paint-element',
                'severity' => $lcpSeverity,
                'title' => 'Preload LCP Hero Image & Prioritize Fetch',
                'wasted_ms' => $audit->lcpMs !== null && $audit->lcpMs > 2500.0 ? (int) round($audit->lcpMs - 2500.0) : 350,
                'wasted_bytes' => 0,
                'recommendation' => 'Your Largest Contentful Paint element is an `<img>`. Inject `<link rel="preload" as="image" href="...">` in `<head>` via `@quinrelPreload` and render the hero image with `fetchpriority="high"` and `loading="eager"`.',
                'blade_snippet' => "@quinrelPreload('{$imgUrl}', 'image')\n<x-quinrel::image src=\"{$imgUrl}\" alt=\"Hero\" :hero=\"true\" />",
                'affected_items' => [$audit->lcpElement],
            ];
        }

        // 2. Render-blocking CSS/JS resources
        if ($audit->renderBlockingResources['wasted_ms'] > 0 || $audit->renderBlockingResources['items'] !== []) {
            $wastedMs = $audit->renderBlockingResources['wasted_ms'];
            $plans[] = [
                'audit_id' => 'render-blocking-resources',
                'severity' => $wastedMs >= 300 ? 'Critical' : 'Warning',
                'title' => "Eliminate Render-Blocking Scripts & Styles (Save {$wastedMs}ms)",
                'wasted_ms' => $wastedMs,
                'wasted_bytes' => 0,
                'recommendation' => 'Migrate synchronous `<script>` and `<link rel="stylesheet">` tags to Laravel `@vite()` ES module bundles or attach `defer` to non-critical scripts.',
                'blade_snippet' => "@vite(['resources/css/app.css', 'resources/js/app.js'])",
                'affected_items' => $audit->renderBlockingResources['items'],
            ];
        }

        // 3. Layout Shift (CLS) elements
        if ($audit->layoutShiftElements !== [] || ($audit->clsScore !== null && $audit->clsScore > 0.1)) {
            $plans[] = [
                'audit_id' => 'layout-shift-elements',
                'severity' => ($audit->clsScore !== null && $audit->clsScore > 0.25) ? 'Critical' : 'Warning',
                'title' => 'Reserve Explicit Dimensions to Prevent Cumulative Layout Shift (CLS)',
                'wasted_ms' => 0,
                'wasted_bytes' => 0,
                'recommendation' => 'Elements are shifting layout during page load. Add explicit `width` and `height` attributes or CSS `aspect-ratio` containers to all images, embeds, and dynamic banners.',
                'blade_snippet' => '<x-quinrel::image src="/images/banner.webp" alt="Banner" width="1200" height="630" />',
                'affected_items' => $audit->layoutShiftElements,
            ];
        }

        // 4. Main-thread Blocking Long Tasks (INP)
        if ($audit->longTasks !== [] || ($audit->inpMs !== null && $audit->inpMs > 200.0)) {
            $totalTaskMs = (int) round(array_sum(array_column($audit->longTasks, 'duration_ms')));
            $plans[] = [
                'audit_id' => 'long-tasks',
                'severity' => ($audit->inpMs !== null && $audit->inpMs > 500.0) ? 'Critical' : 'Warning',
                'title' => 'Defer Main-Thread Long Tasks Impacting INP',
                'wasted_ms' => $totalTaskMs,
                'wasted_bytes' => 0,
                'recommendation' => 'Defer non-critical third-party scripts (Google Tag Manager, chat widgets, analytics) until after first user interaction or execute them inside `requestIdleCallback()`.',
                'blade_snippet' => "<script>\nwindow.addEventListener('load', () => {\n  requestIdleCallback(() => { /* initialize third-party analytics */ });\n});\n</script>",
                'affected_items' => $audit->longTasks,
            ];
        }

        // 5. Unoptimized / Legacy Image Formats
        if ($audit->unoptimizedImages['wasted_bytes'] > 0 || $audit->unoptimizedImages['items'] !== []) {
            $plans[] = [
                'audit_id' => 'uses-optimized-images',
                'severity' => 'Recommendation',
                'title' => 'Serve Modern Image Formats (WebP/AVIF) & Lazy-Load Below-the-Fold Media',
                'wasted_ms' => $audit->unoptimizedImages['wasted_ms'],
                'wasted_bytes' => $audit->unoptimizedImages['wasted_bytes'],
                'recommendation' => 'Convert raster images to WebP/AVIF and apply `loading="lazy" decoding="async"` via `<x-quinrel::image>` or `HeadRenderer::optimizeContentImages()`.',
                'blade_snippet' => '<x-quinrel::image src="/images/feature.webp" alt="Feature" width="800" height="600" />',
                'affected_items' => $audit->unoptimizedImages['items'],
            ];
        }

        // 6. Unminified CSS / JS
        if ($audit->unminifiedAssets['wasted_bytes'] > 0 || $audit->unminifiedAssets['items'] !== []) {
            $plans[] = [
                'audit_id' => 'unminified-assets',
                'severity' => 'Recommendation',
                'title' => 'Minify Production CSS and JavaScript Assets',
                'wasted_ms' => $audit->unminifiedAssets['wasted_ms'],
                'wasted_bytes' => $audit->unminifiedAssets['wasted_bytes'],
                'recommendation' => 'Ensure `npm run build` is executed during deployment so Vite minifies and tree-shakes all CSS and JS assets.',
                'blade_snippet' => null,
                'affected_items' => $audit->unminifiedAssets['items'],
            ];
        }

        return $plans;
    }

    protected function extractSrcFromSnippet(string $snippet): ?string
    {
        if (preg_match('/\bsrc=[\'"]([^\'"]+)[\'"]/i', $snippet, $match)) {
            return trim($match[1]);
        }

        return null;
    }
}
